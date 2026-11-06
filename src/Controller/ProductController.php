<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\AddProductHistory;
use App\Form\ProductType;
use App\Repository\AddProductHistoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Form\AddProductHistoryTypeForm;

#[Route('/editor/product')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findBy([], ['id' => 'ASC']),
        ]);
    }

    #[Route('/editor/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        #[Autowire('%image_directory%')] string $productImagesDirectory,
    ): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->storeProductImage($form->get('image')->getData(), $productImagesDirectory, $product, $slugger);
            $entityManager->persist($product);
            $entityManager->flush();

            $stockHistory = new AddProductHistory();
            $stockHistory->setQte($product->getStock());
            $stockHistory->setProduct($product);
            $stockHistory->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($stockHistory);
            $entityManager->flush();

            $this->addFlash('success', "Le produit a été créé avec succès.");
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/editor/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/editor/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        #[Autowire('%image_directory%')] string $productImagesDirectory,
    ): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->storeProductImage($form->get('image')->getData(), $productImagesDirectory, $product, $slugger);
            $entityManager->flush();

            $this->addFlash('success', "Le produit a été mis à jour avec succès");
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/editor/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', 'Le produit a été supprimé avec succès.');
        } else {
            $this->addFlash('danger', 'La suppression du produit a échoué. Veuillez réessayer.');
        }
        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    private function storeProductImage(
        mixed $image,
        string $directory,
        Product $product,
        SluggerInterface $slugger,
    ): void {
        if (!$image instanceof UploadedFile) {
            return;
        }

        $extension = $image->guessExtension();
        if ($extension === null) {
            throw new \LogicException('Unable to determine the uploaded image extension.');
        }

        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalName)->toString();
        if ($safeFilename === '') {
            $safeFilename = 'product-image';
        }

        $filename = $safeFilename.'-'.bin2hex(random_bytes(8)).'.'.$extension;
        $image->move($directory, $filename);
        $product->setImage($filename);
    }

    #[Route('/add/product/{id}/stock', name: 'app_product_stock_add', methods: ['GET', 'POST'])]
    public function addStock(
        $id,
        EntityManagerInterface $entityManager,
        Request $request,
        ProductRepository $productRepository
    ): Response
    {
        $addStock = new AddProductHistory;
        $form = $this->createForm(AddProductHistoryTypeForm::class, $addStock);
        $form->handleRequest($request);

        $product = $productRepository->find($id);
        if($form->isSubmitted()&&$form->isValid())
            {
                if($addStock->getQte() > 0)
                {
                    $newQte = $product->getStock() + $addStock->getQte();
                    $product->setStock($newQte);

                    $addStock->setCreatedAt(new \DateTimeImmutable());
                    $addStock->setProduct($product);

                    $entityManager->persist($addStock);
                    $entityManager->flush();

                    $this->addFlash('success', 'La quantité a été ajoutée avec succès.');

                    return $this->redirectToRoute('app_product_index');
                }
                else
                {
                    $this->addFlash('danger', 'La quantité doit être supérieure à zéro.');
                    return $this->redirectToRoute('app_product_stock_add', ['id' =>$product->getId()]);
                }
            }
            return $this->render('product/add_stock.html.twig', [
                'form' => $form,
                'product' => $product,
            ]);
    }

    #[Route('/add/product/{id}/stock/history', name: 'app_product_stock_add_history', methods: ['GET'])]
    public function productAddHistory(
        Product $product,
        AddProductHistoryRepository $addProductHistoryRepository,
    ): Response {
        $productAddedHistory = $addProductHistoryRepository->findBy(
            ['product' => $product],
            ['createdAt' => 'DESC', 'id' => 'DESC'],
        );

        return $this->render('product/addedStockHistoryShow.html.twig', [
            'productsAdded' => $productAddedHistory,
            'product' => $product,
        ]);
    }
}
