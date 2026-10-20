<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

test('all application controller routes resolve to existing public methods', function () {
    $checked = 0;
    foreach (Route::getRoutes() as $route) {
        $action = $route->getActionName();
        if (! str_starts_with($action, 'App\\Http\\Controllers\\')) {
            continue;
        }
        [$controller, $method] = array_pad(explode('@', $action, 2), 2, '__invoke');
        expect(class_exists($controller), $action)->toBeTrue();
        expect(method_exists($controller, $method), $action)->toBeTrue();
        expect((new ReflectionMethod($controller, $method))->isPublic(), $action)->toBeTrue();
        $checked++;
    }
    expect($checked)->toBeGreaterThan(0);
});

test('unused avatar endpoints are absent instead of producing controller errors', function () {
    expect(Route::has('profile.avatar.update'))->toBeFalse()
        ->and(Route::has('profile.avatar.destroy'))->toBeFalse();
    $this->actingAs(User::factory()->create());
    $this->post('/profile/avatar')->assertNotFound();
    $this->delete('/profile/avatar')->assertNotFound();
    $this->get('/profile')->assertOk();
});
