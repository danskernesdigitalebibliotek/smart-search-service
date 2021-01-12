<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{

    private string $projectDir;
    private string $destinationDirectory;

    public function __construct(string $bindProjectDir, string $bindDestinationDirectory)
    {
        $this->projectDir = $bindProjectDir;
        $this->destinationDirectory = $bindDestinationDirectory;
    }

    /**
     * Default page listing the generated files.
     *
     * @Route("/", name="index")
     */
    public function index(Request $request): Response
    {
        $finder = new Finder();
        $finder->files()->in($this->destinationDirectory)->name('*.txt')->name('*.csv');;
        $links = [];

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $absoluteFilePath = $file->getRealPath();
                $fileNameWithExtension = $file->getRelativePathname();
                $relativePath = str_replace($this->projectDir.'/public', '', $absoluteFilePath);

                $links[] = [
                    'name' => $fileNameWithExtension,
                    'url' => $request->getUriForPath($relativePath),
                    'date' => $date = \DateTime::createFromFormat( 'U', (string)$file->getCTime()),
                ];
            }
        }

        return $this->render('index/index.html.twig', [
            'links' => $links,
        ]);
    }
}
