<?php
namespace Jeka\ShopAdminBundle\Command;

use \Application\Vespolina\ProductBundle\Document\Product;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearImagesCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this->setName("shop:images:clear");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $im \Jeka\ImageBundle\Document\ImageManager */
        $im = $this->getContainer()->get('jeka.image_manager');

        $images_dir = __DIR__ . '/../../../../web/uploads/images/def';

        $finder = new \Symfony\Component\Finder\Finder();
        $finder->files()->name("*_*.jpg")->in($images_dir);
        foreach ($finder as $file)
        {
            $filename = basename($file);
            list($id,$tmp) = explode('_', $filename);
            //$id = substr($id, 0, -3);
            //$output->writeln($id);

            $image = $im->findImageById($id);
            if (!$image)
            {
                $output->writeln(sprintf('Removing image %s',$file));
                unlink($file);
            }
            else{
                $output->writeln("<info>Left: ".$file."</info>");
            }

        }

    }
}