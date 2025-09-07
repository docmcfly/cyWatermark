<?php


/*
 * This file is TYPO3 forke of LocalCropScaleMaskHelper.
 */

namespace Cylancer\CyWatermark\Resource\Processing;

use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

/**
 * Processes Local Images files
 */
class LocalImageProcessor extends \TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor
{
    use LoggerAwareTrait;

    private array $helpers;

    /**
     * You can create a local images processor with alternative helpers.
     * 
     */
    public function __construct()
    {
        $this->helpers['CropScaleMask'] = new LocalCropScaleMaskHelper(true);
    }



    /**
     * Returns TRUE if this processor can process the given task.
     */
    public function canProcessTask(TaskInterface $task): bool
    {
        return $task->getType() === 'Image'
            && in_array($task->getName(), array_keys($this->helpers), true);
    }

    /**
     * @param string $taskName
     * @return LocalCropScaleMaskHelper
     * @throws \InvalidArgumentException
     */
    protected function getHelperByTaskName($taskName)
    {
        return isset($this->helpers[$taskName])
            ? $this->helpers[$taskName]
            : throw new \InvalidArgumentException('Cannot find helper for task name: "' . $taskName . '"', 1353401352);
    }
}