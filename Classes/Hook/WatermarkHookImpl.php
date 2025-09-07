<?php
namespace Cylancer\CyWatermark\Hook;

use Cylancer\CyWatermark\Domain\Model\SourceOption;
use Cylancer\CyWatermark\Resource\Processing\LocalImageProcessor;
use Cylancer\CyWatermark\Service\Configuration;
use Cylancer\CyWatermark\Service\WatermarkService;
use Exception;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class WatermarkHookImpl implements ProcessorInterface 
{
    
    /**
  * Returns TRUE if this processor can process the given task.
  *
  * @return bool
  */
     public function canProcessTask(TaskInterface $task): bool
    {
        if (
            GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor::class)->canProcessTask($task)
            && $this->shouldProcess($task->getTargetFile())
        ) {
            $sourceFile = $task->getSourceFile();
            //  debug($sourceFile->getMetaData()->get()['tx_cywatermark_watermark_source']);
            switch (SourceOption::tryFrom($sourceFile->getMetaData()->get()['tx_cywatermark_watermark_source'])) {
                case SourceOption::NONE:
                    return false;
                case SourceOption::CATEGORY:
                    /** @var WatermarkService $service */
                    $watermarkService = GeneralUtility::makeInstance(WatermarkService::class);
                    return !empty($watermarkService->get_category_watermark_settings($sourceFile));
                case SourceOption::IMAGE:
                    /** @var WatermarkService $service */
                    $watermarkService = GeneralUtility::makeInstance(WatermarkService::class);
                    return $watermarkService->get_file_watermark_uid($sourceFile) !== false;

                default:
                    throw new Exception("tx_cywatermark_watermark_source = " . $sourceFile->getMetaData()->get()['tx_cywatermark_watermark_source'] . " is not supported");
            }

        }

        return false;
    }
    /**
     * Processes the given task and sets the processing result in the task object.
     */
    public function processTask(TaskInterface $task): void
    {
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        /*
         * Special LocalImageProcessor: This has only one helper for "CropScaleMask" and this helper creates
         * always a processed file.
         */
        /** @var LocalImageProcessor $localImageProcessor */
        $localImageProcessor = GeneralUtility::makeInstance(LocalImageProcessor::class);

        /** @var ProcessedFileRepository $processedFileRepository */
        $processedFileRepository = GeneralUtility::makeInstance(ProcessedFileRepository::class);

        $localImageProcessor->processTask($task);
        try {
            /** @var WatermarkService $service */
            $watermarkService = GeneralUtility::makeInstance(WatermarkService::class);
            $watermarkService->process($task->getTargetFile());
            $processedFileRepository->add($task->getTargetFile());

        } catch (Exception $e) {
            $logger->error(
                \sprintf(
                    'Failed to convert image "%s" to webp with: %s',
                    $task->getTargetFile()->getIdentifier(),
                    $e->getMessage()
                )
            );
        }

    }

    private function shouldProcess(ProcessedFile $processedFile): bool
    {
        if ('Image.CropScaleMask' !== $processedFile->getTaskIdentifier()) {
            return false;
        }
        $processionConfiguration = $processedFile->getProcessingConfiguration();
        $iconSize = $this->getMinimumEdgeLength();

        if ( !($iconSize === false) &&
            (isset($processionConfiguration['maxWidth']) && $processionConfiguration['maxWidth'] <= $iconSize)
            || (isset($processionConfiguration['maxHeight']) && $processionConfiguration['maxHeight'] <= $iconSize)
            || (isset($processionConfiguration['width']) && $processionConfiguration['width'] <= $iconSize)
            || (isset($processionConfiguration['height']) && $processionConfiguration['height'] <= $iconSize)
        ) {
            return false;
        }

        if (!WatermarkService::isSupportedMimeType($processedFile->getOriginalFile()->getMimeType())) {
            return false;
        }

        if (!$this->isStorageLocalAndWritable($processedFile)) {
            return false;
        }
        return true;
    }

    private function hasProcessingConfigurations(array $configuration): bool
    {
        foreach ($configuration as $key => $value) {
            if ($value != null) {
                return true;
            }
        }
        return false;
    }

    private function needsReprocessing(ProcessedFile $processedFile): bool
    {
        return $processedFile->isUpdated()
            || !$this->hasProcessingConfigurations($processedFile->getProcessingConfiguration())
            || $processedFile->isNew()
            || (!$processedFile->usesOriginalFile() && !$processedFile->exists())
            || $processedFile->isOutdated();
    }

    private function isFileInProcessingFolder(ProcessedFile $file): bool
    {
        $storage = $file->getStorage();
        if (null === $storage) {
            return false;
        }

        $processingFolder = $storage->getProcessingFolder();
        if (null === $processingFolder) {
            return false;
        }

        return str_starts_with($file->getIdentifier(), $processingFolder->getIdentifier());
    }

    private function isStorageLocalAndWritable(ProcessedFile $file): bool
    {
        $storage = $file->getStorage();

        // Ignore files in fallback storage (e.g. files from extensions)
        if (null === $storage || 0 === $storage->getStorageRecord()['uid']) {
            return false;
        }

        return 'Local' === $storage->getDriverType() && $storage->isWritable();
    }

    private function originalFileIsInExcludedDirectory(FileInterface $file): bool
    {
        $storageBasePath = $file->getStorage()->getConfiguration()['basePath'] ?? '';
        $filePath = rtrim($storageBasePath, '/') . '/' . ltrim($file->getIdentifier(), '/');
        $excludeDirectories = array_filter(explode(';', Configuration::get('exclude_directories')));

        if (!empty($excludeDirectories)) {
            foreach ($excludeDirectories as $excludedDirectory) {
                if (str_starts_with($filePath, trim($excludedDirectory))) {
                    return true;
                }
            }
        }

        return false;
    }

    private function removeProcessedFile(ProcessedFile $processedFile): void
    {
        try {
            $processedFile->delete(true);
        } catch (Exception $e) {
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
            $logger->error(\sprintf(
                'Failed to remove processed file "%s": %s',
                $processedFile->getIdentifier(),
                $e->getMessage()
            ));
        }
    }



    private static function getMinimumEdgeLength(): int|bool
    {
        $minImageSize = Configuration::get('min_image_edge_length');
        if ($minImageSize != null) {
            return intval($minImageSize);
        }
        return false;
    }


}