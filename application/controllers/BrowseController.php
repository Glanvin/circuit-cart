<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BrowseController extends MY_Controller {

	protected bool $requireLogin = FALSE;

	//Catalog page size for the browse grid (D1).
	private const PER_PAGE = 12;

	//Reads the whitelisted browse query string into a filters array.
	private function browseFilters(): array {
		return [
			'q' => trim((string) $this->input->get('q', true)),
			'category' => trim((string) $this->input->get('category', true)),
			'device_type' => trim((string) $this->input->get('device_type', true)),
			'min_price' => $this->numericParam('min_price'),
			'max_price' => $this->numericParam('max_price'),
		];
	}

	//Returns a non-negative float for a GET parameter, or NULL when absent or
	//not numeric, so a forged query string can never reach SQL unvalidated.
	private function numericParam(string $key): ?float {
		$value = $this->input->get($key, true);
		if ($value === NULL || $value === '' || !is_numeric($value) || (float) $value < 0) {
			return NULL;
		}
		return (float) $value;
	}

	//Clamps the requested page into a valid 1-based page number.
	private function requestedPage(): int {
		$page = (int) $this->input->get('page', true);
		return max(1, $page);
	}

	//Renders the parts browse page, falling back to an empty list on failure.
	public function index()
	{
		$filters = $this->browseFilters();
		$sort = (string) $this->input->get('sort', true);
		if (!in_array($sort, ['name_asc', 'name_desc', 'price_asc', 'price_desc', 'newest'], true)) {
			$sort = 'name_asc';
		}
		$page = $this->requestedPage();
		$wishlistIds = $this->wishlistIds();

		try {
			$options = $this->PartsModel->stockFilterOptions();
			$total = $this->PartsModel->countAllParts($filters);
			$totalPages = max(1, (int) ceil($total / self::PER_PAGE));
			if ($page > $totalPages) {
				$page = $totalPages;
			}
			$parts = $this->PartsModel->getAllParts($filters, $sort, $page, self::PER_PAGE);

			$this->render('pages/browse', [
				'title' => 'Browse',
				'parts' => $parts,
				'filters' => $filters,
				'sort' => $sort,
				'categories' => $options['categories'],
				'device_types' => $options['device_types'],
				'total_results' => $total,
				'pagination' => $this->paginationData($page, $totalPages, $total),
				'wishlist_ids' => $wishlistIds,
			]);
		} catch (Exception $e) {
			$this->toastify->error('We are currently experiencing technical difficulties.');
			$this->render('pages/browse', [
				'title' => 'Browse',
				'parts' => [],
				'filters' => $filters,
				'sort' => $sort,
				'categories' => [],
				'device_types' => [],
				'total_results' => 0,
				'pagination' => [],
				'wishlist_ids' => $wishlistIds,
			]);
		}
	}

	//Saved part ids for the signed-in customer so cards render filled hearts.
	//Guests (and any query failure) get an empty list rather than an error.
	private function wishlistIds(): array {
		$username = (string) $this->session->userdata('username');

		if ($username === '') {
			return [];
		}

		try {
			return $this->WishlistModel->getWishlistPartIds($username);
		} catch (Exception $e) {
			return [];
		}
	}

	//Builds the page-window data the browse view turns into links. Each link
	//carries the current filters and sort so they are preserved across pages.
	private function paginationData(int $page, int $totalPages, int $total): array {
		if ($totalPages <= 1) {
			return [];
		}

		$base = $this->input->get(NULL, true);
		unset($base['page']);

		$linkTo = function (int $target) use ($base) {
			$query = array_filter($base, function ($v) {
				return $v !== '' && $v !== NULL;
			});
			$query['page'] = $target;
			return site_url('browse') . '?' . http_build_query($query);
		};

		$window = range(max(1, $page - 2), min($totalPages, $page + 2));

		return [
			'current' => $page,
			'total_pages' => $totalPages,
			'total' => $total,
			'window' => $window,
			'prev_url' => $page > 1 ? $linkTo($page - 1) : NULL,
			'next_url' => $page < $totalPages ? $linkTo($page + 1) : NULL,
			'page_url' => $linkTo,
		];
	}

	public function product(int $part_id)
	{
		try {
			$part = $this->PartsModel->getPartById($part_id);
			if ((int) $part['is_active'] !== 1) {
				show_404();
				return;
			}
			$part['compatibility'] = json_decode($part['compatibility'] ?? '[]', true) ?: [];
			$part['technical_specs'] = json_decode($part['technical_specs'] ?? '{}', true) ?: [];

			//Related products (D4): same category or device type, never fatal.
			$related = [];
			try {
				$related = $this->PartsModel->getRelatedParts(
					(int) $part['part_id'],
					(string) ($part['category'] ?? ''),
					(string) ($part['device_type'] ?? '')
				);
			} catch (Exception $e) {
				$related = [];
			}

			//Gallery photos beyond the primary image (D3), never fatal.
			$gallery = $this->PartsModel->getProductImages((int) $part['part_id']);

			//Reviews + average rating (E3), never fatal to the product page.
			$reviews = [];
			$rating = ['avg' => 0.0, 'count' => 0];
			try {
				$reviews = $this->ReviewModel->getReviewsForPart((int) $part['part_id']);
				$rating = $this->ReviewModel->getAverageRating((int) $part['part_id']);
			} catch (Exception $e) {
				$reviews = [];
				$rating = ['avg' => 0.0, 'count' => 0];
			}

			//The signed-in shopper's own review, so the form edits it in place.
			$myReview = NULL;
			$username = (string) $this->session->userdata('username');
			if ($username !== '') {
				foreach ($reviews as $review) {
					if (($review['username'] ?? '') === $username) {
						$myReview = $review;
						break;
					}
				}
			}

			$this->render('pages/product_detail', [
				'title' => $part['part_name'],
				'part' => $part,
				'related' => $related,
				'gallery' => $gallery,
				'reviews' => $reviews,
				'rating' => $rating,
				'my_review' => $myReview,
				'wishlist_ids' => $this->wishlistIds(),
			]);
		} catch (AppException $e) {
			show_404();
		}
	}
}
