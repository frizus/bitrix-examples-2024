<?
namespace Frizus\Module\CLI;

use Bitrix\Main\Application;
use Bitrix\Main\Composite\Page;
use Frizus\Module\CLI\CLI;
use Frizus\Module\Helper\FileHelper;

class Cache extends CLI
{
    /**
     * @var \CFileCacheCleaner
     */
    protected $obCacheCleaner;

    /**
     * @see /bitrix/modules/main/admin/cache.php
     */
    public function handle()
    {
        $this->info('Очистка кеша...', true);

        $paths = [
            BX_PERSONAL_ROOT.'/cache/',
            BX_PERSONAL_ROOT.'/managed_cache/',
            BX_PERSONAL_ROOT.'/stack_cache/',
            BX_PERSONAL_ROOT.'/html_pages/',
        ];
        $this->info('Очистка ' . implode(', ', $paths) . ' ...', true);

        $spaceFreed = 0;
        $this->obCacheCleaner->Start();
        while($file = $this->obCacheCleaner->GetNextFile())
        {
            if(!is_string($file)) {
                continue;
            }

            $fileSize = filesize($file);

            if (@unlink($file)) {
                $spaceFreed += $fileSize;
            }
        }
        $this->info('Очистка ' . implode(', ', $paths) . ' ... завершена (' . FileHelper::formatSize($spaceFreed) . ')', true);

        $this->info('Дополнительная очистка папки /bitrix/cache/ ...', true);
        BXClearCache(true, '/');
        $this->info('Дополнительная очистка папки /bitrix/cache/ ... завершена', true);

        global $CACHE_MANAGER, $stackCacheManager;
        $this->info('Дополнительная очистка папки /bitrix/managed_cache/ ...', true);
        $CACHE_MANAGER->CleanAll();
        $this->info('Дополнительная очистка папки /bitrix/managed_cache/ ... завершена', true);

        $this->info('Дополнительная очистка папки /bitrix/stack_cache/ ...', true);
        $stackCacheManager->CleanAll();
        $this->info('Дополнительная очистка папки /bitrix/stack_cache/ ... завершена', true);

        $this->info('Очистка таблицы b_cache_tag...', true);
        Application::getInstance()->getTaggedCache()->deleteAllTags();
        $this->info('Очистка таблицы b_cache_tag... завершена', true);

        $this->info('Очистка композитного кеша...', true);
        Page::getInstance()->deleteAll();
        $this->info('Очистка композитного кеша... завершена', true);

        $this->info('Кеш очищен');

        return true;
    }

    public function init()
    {
        require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/cache_files_cleaner.php");

        $this->obCacheCleaner = new \CFileCacheCleaner('all');
        if(!$this->obCacheCleaner->InitPath('')) {
            $this->error('Не удалось инициализировать класс для чистки кеша');
            return false;
        }

        return true;
    }
}