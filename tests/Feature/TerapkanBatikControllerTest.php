<?php

it('returns a service unavailable response for reset-part when ML is not configured', function () {
    config()->set('services.ml.url', '');

    $response = $this->withoutMiddleware()->postJson(route('api.reset.part'), [
        'session_id' => 'test-session',
        'part' => 'shirt',
        'instance_index' => 0,
    ]);

    $response->assertStatus(503)
        ->assertJsonPath('success', false);
});
