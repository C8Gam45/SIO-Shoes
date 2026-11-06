<?php

namespace App\Service;

use App\Repository\CategoryRepository;

final class CategoryMenuProvider
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {
    }

    public function getCategories(): array
    {
        return $this->categoryRepository->findAll();
    }
}
