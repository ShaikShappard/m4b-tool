<?php


namespace M4bTool\M4bTool\Audio\Tag;


use Exception;
use M4bTool\Audio\EpubChapter;
use M4bTool\Audio\Tag;
use M4bTool\Audio\Tag\TagImproverInterface;
use M4bTool\Chapter\ChapterHandler;
use M4bTool\Parser\EpubParser;
use Sandreas\Time\TimeUnit;
use SplFileInfo;
use Throwable;

class ChaptersFromEpub implements TagImproverInterface
{


    /**
     * @var array
     */
    protected $chaptersFromEpub;
    /**
     * @var ChapterHandler
     */
    protected $chapterHandler;

    public function __construct(array $chaptersFromEpub = [], ChapterHandler $chapterHandler = null)
    {
        $this->chaptersFromEpub = $chaptersFromEpub;
        $this->chapterHandler = $chapterHandler;
    }

    public function getChaptersFromEpub()
    {
        return $this->chaptersFromEpub;
    }

    public static function fromFile(ChapterHandler $chapterHandler, SplFileInfo $reference, TimeUnit $totalDuration, array $chapterIndexesToRemove = [], $fileName = null)
    {
        try {
            $path = $reference->isDir() ? $reference : new SplFileInfo($reference->getPath());
            $fileName = $fileName ? $fileName : $reference->getBasename($reference->getExtension()) . "epub";


            $globPattern = $path . "/" . $fileName;
            $files = glob($globPattern);
            if (!is_array($files) || count($files) === 0) {
                return new static();
            }

            $fileToLoad = new SplFileInfo($files[0]);
            if ($fileToLoad->isFile()) {
                $epubParser = new EpubParser($fileToLoad);
                $chapters = $epubParser->parseTocToChapters($totalDuration, $chapterIndexesToRemove);
                return new static($chapters, $chapterHandler);
            }
        } catch (Throwable $e) {
            // ignore
        }
        return new static();
    }

    /**
     * @param Tag $tag
     * @return Tag
     * @throws Exception
     */
    public function improve(Tag $tag): Tag
    {
        $chaptersWithoutIgnored = array_filter($this->chaptersFromEpub, function (EpubChapter $chapter) {
            return !$chapter->isIgnored();
        });


        if (count($chaptersWithoutIgnored) === 0) {
            return $tag;
        }

        $tag->chapters = $this->chapterHandler->overloadTrackChaptersKeepUnique($chaptersWithoutIgnored, $tag->chapters);
        return $tag;
    }


}
