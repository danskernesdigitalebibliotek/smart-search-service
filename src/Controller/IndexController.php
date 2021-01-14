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
        $finder->files()->in($this->destinationDirectory)->name('*.txt')->name('*.csv');
        $links = [];

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $absoluteFilePath = $file->getRealPath();
                $fileNameWithExtension = $file->getRelativePathname();
                $relativePath = str_replace($this->projectDir.'/public', '', $absoluteFilePath);

                $links[] = [
                    'name' => $fileNameWithExtension,
                    'url' => $relativePath,
                    'date' => $date = \DateTime::createFromFormat('U', (string) $file->getCTime()),
                    'size' => $this->formatByteSize($file->getSize()),
                ];
            }
        }

        return $this->render('index/index.html.twig', [
            'links' => $links,
        ]);
    }

    /**
     * Format file size bytes into formatted size string.
     *
     * @param int $bytes
     *   The size in bytes.
     *
     * @return string
     *   Formatted size string.
     */
    private function formatByteSize(int $bytes): string
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
}
