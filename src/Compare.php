<?php

class Compare {
    private $basePath,
            $differenceThreshold = 7;
    
    public function init($basePath) {
        $this->basePath = $basePath;

        $findSourceImagesFolder = $this->basePath . 'origin-with-desired-size';
        $it = new \RecursiveDirectoryIterator($findSourceImagesFolder);

        foreach (new \RecursiveIteratorIterator($it) as $file) {

            if ($file->getExtension() == 'png' || $file->getExtension() == 'jpg' || $file->getExtension() == 'jpeg') {
                $imagickSourceImageWithDesiredSize = new \Imagick();
                $imagickSourceImageWithDesiredSize->readimage($file);
                $imagickSourceImageWithDesiredSize->trimImage(0);
                $this->findImageOnDestinationDirectory($imagickSourceImageWithDesiredSize, $file);
            }
        }
    }

    private function findImageOnDestinationDirectory($imagickSourceImageWithDesiredSize, $filesource) {
        $filePathWithDesiredSize = $filesource->getPathName();

        $imageSourceSize = [
            'width' => $imagickSourceImageWithDesiredSize->getImageWidth(), 
            'height' => $imagickSourceImageWithDesiredSize->getImageHeight()
        ];

        $findFolder = $this->basePath . 'origin-with-desired-name';
        $it = new \RecursiveDirectoryIterator($findFolder);
        
        foreach (new \RecursiveIteratorIterator($it) as $file) {
            $filePathWithDesiredName = $file->getPathName();

            if (in_array($file->getExtension(), ['png', 'jpg', 'jpeg'])) {
                $loockupImage = new \Imagick();
                $loockupImage->readimage($filePathWithDesiredName);
                $loockupImage->trimImage(0);
                $loockupImage->resizeImage($imageSourceSize['width'], $imageSourceSize['height'], \imagick::FILTER_LANCZOS, 1, false);
                
                $difference = $this->compareTwoImagesAndReturnDifference(
                    $imagickSourceImageWithDesiredSize, 
                    $loockupImage, 
                    $this->basePath . "compared-images/result-{$file->getFileName()}"
                );
                
                if ($difference < $this->differenceThreshold) {
                    print_r("Found: " . $filePathWithDesiredName. "\n");
                    $relativePath = str_replace($this->basePath, '', $file->getPath());
                    
                    if ($this->copyImageToDestinationFolderAndCorrectName($filePathWithDesiredSize, $relativePath, $file->getFilename())){
                        unlink($filePathWithDesiredName);
                        unlink($filePathWithDesiredSize);
                    }

                    break;
                }
            }
        }
    }

    private function copyImageToDestinationFolderAndCorrectName($sourceImagePath, $relativePath, $correctDestinationName) {
        $destionationPath = $this->basePath . 'result-images-with-correct-image-name-and-image-size/' . $relativePath;
        $this->make_dir($destionationPath);
        
        if (copy($sourceImagePath, $destionationPath . '/' . $correctDestinationName)){
            return true;
        }
        
        return false;
    }

    private function make_dir($path, $permissions = 0777){
        return is_dir($path) || mkdir($path, $permissions, true);
    }

    private static function compareTwoImagesAndReturnDifference($srcImage, $dstImage, $dstImagePath) {
        $result = $srcImage->compareimages($dstImage, \Imagick::METRIC_MEANSQUAREERROR);

        $basename = basename($dstImagePath);
        $dirname = dirname($dstImagePath);

        $fileNameData = explode('.', $basename);
        $fileName = $fileNameData[0];
        $fileExtension = $fileNameData[1];

        $diffPath = "$dirname/$fileName-diff.$fileExtension";
        $result[0]->writeimage($diffPath);

        return round($result[1] * 100, 3);
    }
}
