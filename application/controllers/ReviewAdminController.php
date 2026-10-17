<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ReviewAdminController extends MY_Controller {

	protected bool $employeePortal = TRUE;

	protected string $navbar = 'templates/navbar_employees';

	protected array $allowedRoles = ['admin', 'manager'];

	private const PER_PAGE = 15;

	//Lists product reviews with status/search filters and pagination.
	public function index()
	{
		$filters = $this->reviewFilters();
		$sort    = $this->input->get('sort', true) ?: 'newest';
		$page    = max(1, (int) ($this->input->get('page', true) ?: 1));
		$reviews = [];
		$total   = 0;

		try {
			$total   = $this->ReviewModel->countAllReviews($filters);
			$reviews = $this->ReviewModel->getAllReviews($filters, $sort, $page, self::PER_PAGE);
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		$totalPages = max(1, (int) ceil($total / self::PER_PAGE));

		$this->render('pages/employees/review_table', [
			'title' => 'Product Reviews',
			'reviews' => $reviews,
			'filters' => $filters,
			'sort' => $sort,
			'total' => $total,
			'pagination' => $this->paginationData($page, $totalPages, $total),
		]);
	}

	//Publishes or hides a review.
	public function status(int $id, string $status)
	{
		try {
			$this->ReviewModel->setReviewStatus($id, $status);
			$this->toastify->success($status === 'hidden' ? 'Review hidden from the storefront.' : 'Review published.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('employee/reviews', 'refresh');
	}

	//Permanently deletes a review.
	public function delete(int $id)
	{
		try {
			$this->ReviewModel->deleteReview($id);
			$this->toastify->success('Review deleted.');
		} catch (AppException $e) {
			$this->toastify->error($e->toToast());
		}

		redirect('employee/reviews', 'refresh');
	}

	//Allowed list filters (status + free-text search).
	private function reviewFilters(): array
	{
		$status = (string) ($this->input->get('status', true) ?: '');
		$q      = trim((string) ($this->input->get('q', true) ?: ''));

		$filters = ['q' => $q];

		if (in_array($status, ['published', 'hidden'], true)) {
			$filters['status'] = $status;
		}

		return $filters;
	}

	//Builds the pagination payload, preserving the active filters.
	private function paginationData(int $page, int $totalPages, int $total): array
	{
		if ($totalPages <= 1) {
			return [];
		}

		$base = array_filter([
			'status' => $this->input->get('status', true) ?: '',
			'q' => $this->input->get('q', true) ?: '',
			'sort' => $this->input->get('sort', true) ?: '',
		], fn($value) => $value !== '');

		$linkTo = function (int $target) use ($base): string {
			$query = $base;
			$query['page'] = $target;
			return site_url('employee/reviews') . '?' . http_build_query($query);
		};

		return [
			'current' => $page,
			'total_pages' => $totalPages,
			'total' => $total,
			'window' => range(max(1, $page - 2), min($totalPages, $page + 2)),
			'prev_url' => $page > 1 ? $linkTo($page - 1) : NULL,
			'next_url' => $page < $totalPages ? $linkTo($page + 1) : NULL,
			'page_url' => $linkTo,
		];
	}
}
