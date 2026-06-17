#!/bin/sh
# One-shot sidecar: wait for SeaweedFS S3 to be reachable, then create the
# application buckets. SeaweedFS with no identity config accepts any signature,
# so the dummy credentials below are fine.
set -e

ENDPOINT="${AWS_ENDPOINT:-http://seaweedfs:8333}"
BUCKETS="${BUCKETS:-backups exports imports uploads}"

export AWS_ACCESS_KEY_ID="${AWS_ACCESS_KEY_ID:-seaweedfs}"
export AWS_SECRET_ACCESS_KEY="${AWS_SECRET_ACCESS_KEY:-seaweedfs}"
export AWS_DEFAULT_REGION="${AWS_DEFAULT_REGION:-us-east-1}"

echo "Waiting for SeaweedFS S3 at ${ENDPOINT} ..."
i=0
until aws --endpoint-url "${ENDPOINT}" s3 ls >/dev/null 2>&1; do
    i=$((i + 1))
    if [ "$i" -gt 60 ]; then
        echo "SeaweedFS did not become ready in time" >&2
        exit 1
    fi
    sleep 2
done

for b in ${BUCKETS}; do
    if aws --endpoint-url "${ENDPOINT}" s3 ls "s3://${b}" >/dev/null 2>&1; then
        echo "bucket ${b} already exists"
    else
        echo "creating bucket ${b}"
        aws --endpoint-url "${ENDPOINT}" s3 mb "s3://${b}"
    fi
done

echo "SeaweedFS buckets ready: ${BUCKETS}"
