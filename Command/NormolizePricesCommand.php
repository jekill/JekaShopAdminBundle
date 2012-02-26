<?php

namespace Jeka\ShopAdminBundle\Command;

use \Application\Vespolina\ProductBundle\Document\Product;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NormolizePricesCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this->setName("shop:products:normolize-prices");
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pm = $this->getContainer()->get('vespolina.product_manager');
        $products = $pm->createFindAllQuery()
            ->field('type')->equals(Product::PHYSICAL)
            ->getQuery()
            ->execute();

        foreach ($products as $product) {
            $price = $product->getPrice();
            $price = round($price/5)*5;
            $product->setPrice($price);
            $pm->updateProduct($product,false);
        }
        unset($product);
        $pm->flush();


    }
}