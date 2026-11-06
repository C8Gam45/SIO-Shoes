<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\SubCategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $data = $productRepository
            ->createQueryBuilder('product')
            ->orderBy('product.name', 'ASC');
        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('home/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/home/product/{id}', name: 'app_home_product_show', methods: ['GET'])]
    public function show($id, Product $product, ProductRepository $productRepository,
    CategoryRepository $categoryRepository): Response
    {
        $lastProducts = $productRepository->findBy([], ['id' => 'DESC'], limit: 5);

        return $this->render('home/show.html.twig', [
            'product' => $product,
            'lastProducts' => $lastProducts,
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/home/product/subcategory/{id}/filter', name: 'app_home_product_filter', methods: ['GET'])]
    public function filter(
        int $id,
        CategoryRepository $categoryRepository,
        SubCategoryRepository $subCategoryRepository,
        ProductRepository $productRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $subCategory = $subCategoryRepository->find($id);
        if ($subCategory === null) {
            throw $this->createNotFoundException('Sous-catégorie introuvable.');
        }

        $productsQuery = $productRepository
            ->createQueryBuilder('product')
            ->innerJoin('product.subCategories', 'subCategory')
            ->andWhere('subCategory = :subCategory')
            ->setParameter('subCategory', $subCategory)
            ->orderBy('product.name', 'ASC');
        $products = $paginator->paginate(
            $productsQuery,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('home/filter.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll(),
            'category' => $subCategory->getCategory(),
            'subCategory' => $subCategory,
        ]);
    }
}
