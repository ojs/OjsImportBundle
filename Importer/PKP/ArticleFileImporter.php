<?php

namespace OkulBilisim\OjsImportBundle\Importer\PKP;

use Jb\Bundle\FileUploaderBundle\Entity\FileHistory;
use Ojs\JournalBundle\Entity\Article;
use Ojs\JournalBundle\Entity\ArticleFile;
use OkulBilisim\OjsImportBundle\Entity\PendingDownload;
use OkulBilisim\OjsImportBundle\Helper\FileHelper;
use OkulBilisim\OjsImportBundle\Importer\Importer;

class ArticleFileImporter extends Importer
{
    public function importArticleFiles($article, $oldId, $slug)
    {
        $fileIdsSql = "SELECT DISTINCT file_id FROM article_files WHERE article_id = :id";
        $fileIdsStatement = $this->dbalConnection->prepare($fileIdsSql);
        $fileIdsStatement->bindValue('id', $oldId);
        $fileIdsStatement->execute();

        $fileIds = $fileIdsStatement->fetchAll();
        $articleFiles = array();

        foreach ($fileIds as $fileId) {
            $articleFileSql = "SELECT file_id, file_type, original_file_name, revision FROM article_files" .
            " WHERE article_id = :id AND file_id = :fileId ORDER BY revision DESC LIMIT 1";
            $articleFileStatement = $this->dbalConnection->prepare($articleFileSql);
            $articleFileStatement->bindValue('fileId', $fileId['file_id']);
            $articleFileStatement->bindValue('id', $oldId);
            $articleFileStatement->execute();

            $articleFiles[] = $articleFileStatement->fetch();
        }
        
        foreach ($articleFiles as $articleFile) {
            $this->importArticleFile($articleFile, $oldId, $article, $slug);
        }
    }

    /**
     * @param int     $pkpArticleFile
     * @param int     $oldArticleId
     * @param Article $article
     * @param string  $slug
     */
    public function importArticleFile($pkpArticleFile, $oldArticleId, $article, $slug)
    {
        $this->consoleOutput->writeln("Reading article file #" . $pkpArticleFile['file_id'] . "... ", true);

        $galleysSql = "SELECT galley_id, article_id, locale, label FROM article_galleys " .
            "WHERE article_id = :article_id AND file_id = :id";
        $galleysStatement = $this->dbalConnection->prepare($galleysSql);
        $galleysStatement->bindValue('article_id', $oldArticleId);
        $galleysStatement->bindValue('id', $pkpArticleFile['file_id']);
        $galleysStatement->execute();

        $pkpGalleys = $galleysStatement->fetchAll();

        foreach ($pkpGalleys as $galley) {
            $locale = !empty($galley['locale']) ? substr($galley['locale'], 0, 2) : 'en';
            $label = !empty($galley['label']) ? $galley['label'] : '-';
            $version = !empty($pkpArticleFile['revision']) ? $pkpArticleFile['revision'] : 0;
            $filename = sprintf('imported/%s/%s.%s',
                $galley['article_id'],
                $galley['galley_id'],
                FileHelper::$mimeToExtMap[$pkpArticleFile['file_type']]);

            $articleFile = new ArticleFile();
            $articleFile->setFile($filename);
            $articleFile->setArticle($article);
            $articleFile->setVersion($version);
            $articleFile->setTitle($label);
            $articleFile->setLangCode($locale);
            $articleFile->setDescription('-');
            $articleFile->setType(0);

            $history = new FileHistory();
            $history->setFileName($filename);
            $history->setOriginalName($pkpArticleFile['original_file_name']);
            $history->setType('articlefiles');

            $source = sprintf('%s/article/download/%s/%s', $slug, $galley['article_id'], $galley['galley_id']);
            $target = sprintf('/../web/uploads/articlefiles/imported/%s/%s.%s',
                $galley['article_id'],
                $galley['galley_id'],
                FileHelper::$mimeToExtMap[$pkpArticleFile['file_type']]);

            $pendingDownload = new PendingDownload();
            $pendingDownload->setSource($source);
            $pendingDownload->setTarget($target);

            $this->em->persist($pendingDownload);
            $this->em->persist($articleFile);
            $this->em->persist($history);
        }
    }
}