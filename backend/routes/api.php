<?php
declare(strict_types=1);

function api_route_definitions(): array {
    static $routes = [
        ['POST', '#^/api/auth/register$#D', static fn() => AuthController::register()],
        ['POST', '#^/api/auth/login$#D', static fn() => AuthController::login()],
        ['POST', '#^/api/auth/logout$#D', static fn() => AuthController::logout()],
        ['GET', '#^/api/auth/me$#D', static fn() => AuthController::me()],
        ['PUT', '#^/api/auth/me$#D', static fn() => AuthController::updateMe()],
        ['POST', '#^/api/auth/me/avatar$#D', static fn() => AuthController::uploadAvatar()],
        ['POST', '#^/api/auth/forgot-password$#D', static fn() => AuthController::forgotPassword()],
        ['POST', '#^/api/auth/reset-password$#D', static fn() => AuthController::resetPassword()],
        ['POST', '#^/api/auth/change-password$#D', static fn() => AuthController::changePassword()],
        ['POST', '#^/api/admin/handoff$#D', static fn() => AdminHandoffController::create()],
        ['POST', '#^/api/admin/handoff/consume$#D', static fn() => AdminHandoffController::consume()],
        ['GET', '#^/api/destinations$#D', static fn() => DestinationController::index()],
        ['GET', '#^/api/destinations/featured$#D', static fn() => DestinationController::featured()],
        ['GET', '#^/api/destinations/(\d+)$#D', static fn(array $m) => DestinationController::show((int)$m[1])],
        ['GET', '#^/api/destinations/search$#D', static fn() => DestinationController::index()],
        ['GET', '#^/api/categories$#D', static fn() => ReferenceController::categories()],
        ['GET', '#^/api/regions$#D', static fn() => ReferenceController::regions()],
        ['GET', '#^/api/departements$#D', static fn() => ReferenceController::departements()],
        ['GET', '#^/api/districts$#D', static fn() => ReferenceController::districts()],
        ['GET', '#^/api/localites$#D', static fn() => ReferenceController::localites()],
        ['GET', '#^/api/langues$#D', static fn() => ReferenceController::languages()],
        ['GET', '#^/api/favorites$#D', static fn() => FavoriteController::index()],
        ['POST', '#^/api/favorites$#D', static fn() => FavoriteController::store()],
        ['DELETE', '#^/api/favorites/(\d+)$#D', static fn(array $m) => FavoriteController::destroy((int)$m[1])],
        ['GET', '#^/api/transports$#D', static fn() => TransportController::transports()],
        ['GET', '#^/api/itineraires$#D', static fn() => TransportController::itineraries()],
        ['POST', '#^/api/itineraires$#D', static fn() => TransportController::search()],
        ['POST', '#^/api/reservations$#D', static fn() => TransportController::reserve()],
        ['GET', '#^/api/reservations$#D', static fn() => TransportController::reservations()],
        ['GET', '#^/api/reservations/(\d+)$#D', static fn(array $m) => TransportController::reservationDetails((int)$m[1])],
        ['POST', '#^/api/reservations/(\d+)/cancel$#D', static fn(array $m) => TransportController::cancelReservation((int)$m[1])],
        ['GET', '#^/api/support/threads$#D', static fn() => SupportController::index()],
        ['POST', '#^/api/support/threads$#D', static fn() => SupportController::create()],
        ['GET', '#^/api/support/threads/(\d+)$#D', static fn(array $m) => SupportController::show((int)$m[1])],
        ['POST', '#^/api/support/threads/(\d+)/messages$#D', static fn(array $m) => SupportController::reply((int)$m[1])],
        ['GET', '#^/api/videos$#D', static fn() => VideoController::index()],
        ['POST', '#^/api/videos$#D', static fn() => VideoController::upload()],
        ['GET', '#^/api/videos/(\d+)/comments$#D', static fn(array $m) => VideoController::comments((int)$m[1])],
        ['POST', '#^/api/videos/(\d+)/like$#D', static fn(array $m) => VideoController::toggleLike((int)$m[1])],
        ['POST', '#^/api/videos/(\d+)/comment$#D', static fn(array $m) => VideoController::comment((int)$m[1])],
        ['GET', '#^/api/videos/(\d+)$#D', static fn(array $m) => VideoController::show((int)$m[1])],
        ['GET', '#^/api/quizzes$#D', static fn() => QuizController::index()],
        ['GET', '#^/api/quizzes/results$#D', static fn() => QuizController::results()],
        ['POST', '#^/api/quizzes/(\d+)/submit$#D', static fn(array $m) => QuizController::submit((int)$m[1])],
        ['GET', '#^/api/quizzes/(\d+)$#D', static fn(array $m) => QuizController::show((int)$m[1])],
        ['GET', '#^/api/passport$#D', static fn() => PassportController::show()],
        ['GET', '#^/api/passport/badges$#D', static fn() => PassportController::badges()],
        ['GET', '#^/api/passport/visits$#D', static fn() => PassportController::visits()],
        ['POST', '#^/api/passport/visits$#D', static fn() => PassportController::recordVisit()],
        ['GET', '#^/api/notifications$#D', static fn() => NotificationController::index()],
        ['PUT', '#^/api/notifications/(\d+)/read$#D', static fn(array $m) => NotificationController::markRead((int)$m[1])],
        ['GET', '#^/api/settings$#D', static fn() => SettingsController::show()],
        ['PUT', '#^/api/settings$#D', static fn() => SettingsController::update()],
        ['GET', '#^/api/patrimoines$#D', static fn() => HeritageController::index()],
        ['GET', '#^/api/visites-virtuelles$#D', static fn() => HeritageController::virtualVisits()],
        ['POST', '#^/api/patrimoines/(\d+)/view$#D', static fn(array $m) => HeritageController::recordView((int)$m[1])],
    ];

    return $routes;
}

function dispatch_api_route(string $method, string $path): bool {
    foreach (api_route_definitions() as [$routeMethod, $pattern, $handler]) {
        if ($routeMethod !== $method || !preg_match($pattern, $path, $matches)) continue;
        $handler($matches);
        return true;
    }
    return false;
}
