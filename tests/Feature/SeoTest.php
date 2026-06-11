<?php

use App\Support\Seo;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the welcome page with complete SEO metadata', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('seo.title', 'Roel R. Longcop — Full Stack Software Developer')
            ->where('seo.type', 'profile')
            ->where('seo.robots', 'index,follow')
            ->whereType('seo.description', 'string')
            ->whereType('seo.canonical', 'string')
            ->has('seo.json_ld')
            ->where('seo.json_ld.@type', 'Person')
        );
});

it('renders the contact page with its own SEO metadata', function () {
    $this->get('/contact')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Contact')
            ->where('seo.title', 'Contact')
            ->whereType('seo.description', 'string')
        );
});

it('fills every SEO field from defaults', function () {
    $seo = Seo::make(title: 'Hello', description: 'World')->toArray();

    expect($seo)->toHaveKeys([
        'title', 'description', 'canonical', 'image', 'type', 'robots',
        'keywords', 'locale', 'site_name', 'twitter_card', 'twitter_site', 'json_ld',
    ]);
    expect($seo['title'])->toBe('Hello');
    expect($seo['robots'])->toBe('index,follow');
    expect($seo['type'])->toBe('website');
    expect($seo['canonical'])->toBeString();
});

it('builds SEO metadata from a model', function () {
    $model = new class
    {
        public string $meta_title = 'My Page';

        public string $meta_description = 'A described page';

        public ?string $og_image = 'https://cdn.example.com/og.png';
    };

    $seo = Seo::fromModel($model)->toArray();

    expect($seo['title'])->toBe('My Page');
    expect($seo['description'])->toBe('A described page');
    expect($seo['image'])->toBe('https://cdn.example.com/og.png');
    expect($seo['type'])->toBe('article');
});

it('serves an XML sitemap of public URLs', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/xml');
    $response->assertSee('<urlset', false);
    $response->assertSee(route('home'), false);
    $response->assertSee(route('contact'), false);
});

it('serves robots.txt referencing the sitemap', function () {
    $response = $this->get('/robots.txt');

    $response->assertOk();
    $response->assertSee('User-agent: *');
    $response->assertSee('Sitemap: '.route('sitemap'));
});
