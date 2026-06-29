<?php

use App\Models\UploadSession;
use App\Support\ChunkStorage\S3MultipartChunkStorage;
use Aws\S3\S3Client;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;

/**
 * Storage::fake can't exercise the S3 multipart path (it's local), so this mocks
 * the S3 client behind the `uploads` disk and asserts the SDK calls + that parts
 * are replayed to completeMultipartUpload in ascending order.
 */
beforeEach(function (): void {
    $this->client = Mockery::mock(S3Client::class);

    $adapter = Mockery::mock(AwsS3V3Adapter::class);
    $adapter->shouldReceive('getClient')->andReturn($this->client);
    Storage::shouldReceive('disk')->with('uploads')->andReturn($adapter);

    config()->set('filesystems.disks.uploads.bucket', 'uploads');
});

it('drives the S3 multipart API and orders parts on complete', function (): void {
    $session = UploadSession::factory()->create([
        'driver' => 's3',
        'object_key' => '2026/06/abc.pdf',
        'total_chunks' => 2,
    ]);

    $storage = new S3MultipartChunkStorage;

    // begin → createMultipartUpload, returns the upload id to persist.
    $this->client->shouldReceive('createMultipartUpload')->once()
        ->with(Mockery::on(fn (array $a): bool => $a['Bucket'] === 'uploads' && $a['Key'] === '2026/06/abc.pdf'))
        ->andReturn(['UploadId' => 'UP123']);

    expect($storage->begin($session))->toBe(['s3_upload_id' => 'UP123']);
    $session->update(['s3_upload_id' => 'UP123']);

    // putPart → uploadPart, returns the ETag verbatim.
    $this->client->shouldReceive('uploadPart')->once()
        ->with(Mockery::on(fn (array $a): bool => $a['PartNumber'] === 1
            && $a['UploadId'] === 'UP123'
            && $a['Body'] === 'chunk-one'))
        ->andReturn(['ETag' => '"etag1"']);

    $result = $storage->putPart($session, 1, 'chunk-one');
    expect($result['etag'])->toBe('"etag1"')->and($result['size'])->toBe(9);

    // Record parts out of order to prove assemble sorts them.
    $session->parts()->create(['part_number' => 2, 'etag' => '"etag2"', 'size' => 9]);
    $session->parts()->create(['part_number' => 1, 'etag' => '"etag1"', 'size' => 9]);

    $this->client->shouldReceive('completeMultipartUpload')->once()
        ->with(Mockery::on(fn (array $a): bool => $a['MultipartUpload']['Parts'] === [
            ['PartNumber' => 1, 'ETag' => '"etag1"'],
            ['PartNumber' => 2, 'ETag' => '"etag2"'],
        ]))
        ->andReturn(['Location' => 's3://uploads/2026/06/abc.pdf']);

    $storage->assemble($session);
});

it('aborts the multipart upload', function (): void {
    $session = UploadSession::factory()->create([
        'driver' => 's3',
        'object_key' => '2026/06/def.pdf',
        's3_upload_id' => 'UP999',
    ]);

    $this->client->shouldReceive('abortMultipartUpload')->once()
        ->with(Mockery::on(fn (array $a): bool => $a['UploadId'] === 'UP999'))
        ->andReturn([]);

    (new S3MultipartChunkStorage)->abort($session);
});
