<?php
namespace Cylancer\CyWatermark\Hook;

use Cylancer\CyWatermark\Service\WatermarkService;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;

class DataHandlerHook implements SingletonInterface
{

  public function __construct(
    private readonly WatermarkService $watermarkService,
  ) {
  }

  /**
   * Generate a different preview link     *
   *
   * @param string $status status
   * @param string $table table name
   * @param int $recordUid id of the record
   * @param array $fields fieldArray
   * @param DataHandler $parentObject parent Object
   */
  public function processDatamap_afterDatabaseOperations(
    $status,
    $table,
    $recordUid,
    array $fields,
    DataHandler $parentObject
  ): void {
    if ($status === 'update') {
      if ($table === 'sys_category' && $this->watermarksFieldChanged($fields)) {
        $this->watermarkService->clearProcessedFileCacheFromCategory($recordUid);
      } elseif ($table === 'sys_file_metadata' && ($this->watermarksFieldChanged($fields)|| $this->categoriesChanged($fields))) {
        $this->watermarkService->clearProcessedFileCacheFromFileMetaData($recordUid);
      }
    }
  }


  private function watermarksFieldChanged(array $fields): bool
  {
    foreach (array_keys($fields) as $key) {
      if (str_starts_with($key, 'tx_cywatermark_watermark_')) {
        return true;
      }
    }
    return false;
  }


  private function categoriesChanged(array $fields): bool
  {
    foreach (array_keys($fields) as $key) {
      if ($key == 'categories') {
        return true;
      }
    }
    return false;
  }

}