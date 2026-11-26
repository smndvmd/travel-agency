<?php
declare(strict_types=1);
namespace Tourism\Controllers;
use Tourism\Core\Controller;

final class SiteController extends Controller
{
    public function home(): void
    {
        require_once BASE_PATH . '/config.php';
        $toursRepo = new \Tourism\Repositories\TourRepository();
        $tourPage = $toursRepo->search($_GET, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
        $this->render('tours/index', [
            'title' => 'Турҳо', 'active' => 'tours',
            'regions' => $toursRepo->activeRegions(), 'tours' => $tourPage['items'],
            'tourPage' => $tourPage, 'filters' => $_GET,
            'hs' => (new \Tourism\Repositories\DashboardRepository())->homeStats(),
        ]);
    }
    public function tour(int $id): void
    {
        require_once BASE_PATH . '/config.php';
        $tour = (new \Tourism\Repositories\TourRepository())->findActive($id);
        if (!$tour) {
            $this->render('errors/error', ['title' => 'Тур ёфт нашуд', 'code' => 404, 'message' => 'Тур ёфт нашуд.'], 404, null);
            return;
        }
        $photos = (new \Tourism\Repositories\PhotoRepository())->pathsForTour($id);
        $dates = (new \Tourism\Repositories\DateRepository())->forTour($id, true);
        $reviews = (new \Tourism\Repositories\ReviewRepository())->forTour($id);
        $user = me();
        $myReview = null;
        $bookingId = 0;
        if ($user && $user['role'] === 'tourist') {
            $bookings = new \Tourism\Repositories\BookingRepository();
            $bookingId = $bookings->findReviewable((int)$user['id'], $id) ?? 0;
            $myReview = (new \Tourism\Repositories\ReviewRepository())->findByUserAndTour((int)$user['id'], $id);
        }
        $this->render('tours/show', [
            'title' => $tour['title'], 'active' => 'tours', 't' => $tour, 'photos' => $photos,
            'dates' => $dates, 'reviews' => $reviews,
            'avg' => $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0,
            'u' => $user, 'myReview' => $myReview, 'bookingId' => $bookingId,
            'canReview' => $bookingId && !$myReview,
        ]);
    }
}
