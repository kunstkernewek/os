<?php
// Wiki plugin, https://github.com/datenstrom/yellow-plugins/tree/master/wiki
// Copyright (c) 2013-2017 Datenstrom, https://datenstrom.se
// This file may be used and distributed under the terms of the public license.

class YellowWiki
{
	const VERSION = "0.7.1";
	var $yellow;			//access to API
	
	// Handle initialisation
	function onLoad($yellow)
	{
		$this->yellow = $yellow;
		$this->yellow->config->setDefault("wikiLocation", "");
		$this->yellow->config->setDefault("wikiNewLocation", "@title");
		$this->yellow->config->setDefault("wikiPagesMax", "10");
		$this->yellow->config->setDefault("wikiPaginationLimit", "30");
	}

	// Handle page content parsing of custom block
	function onParseContentBlock($page, $name, $text, $shortcut)
	{
		$output = null;
		if($name=="wikiauthors" && $shortcut)
		{
			list($location, $pagesMax) = $this->yellow->toolbox->getTextArgs($text);
			if(empty($location)) $location = $this->yellow->config->get("wikiLocation");
			if(empty($pagesMax)) $pagesMax = $this->yellow->config->get("wikiPagesMax");
			$wiki = $this->yellow->pages->find($location);
			$pages = $this->getWikiPages($location);
			$page->setLastModified($pages->getModified());
			$authors = array();
			foreach($pages as $page) if($page->isExisting("author")) foreach(preg_split("/\s*,\s*/", $page->get("author")) as $author) ++$authors[$author];
			if(count($authors))
			{
				$authors = $this->yellow->lookup->normaliseUpperLower($authors);
				if($pagesMax!=0 && count($authors)>$pagesMax)
				{
					uasort($authors, strnatcasecmp);
					$authors = array_slice($authors, -$pagesMax);
				}
				uksort($authors, strnatcasecmp);
				$output = "<div class=\"".htmlspecialchars($name)."\">\n";
				$output .= "<ul>\n";
				foreach($authors as $key=>$value)
				{
					$output .= "<li><a href=\"".$wiki->getLocation(true).$this->yellow->toolbox->normaliseArgs("author:$key")."\">";
					$output .= htmlspecialchars($key)."</a></li>\n";
				}
				$output .= "</ul>\n";
				$output .= "</div>\n";
			} else {
				$page->error(500, "Wikiauthors '$location' does not exist!");
			}
		}
		if($name=="wikipages" && $shortcut)
		{
			list($location, $pagesMax) = $this->yellow->toolbox->getTextArgs($text);
			if(empty($location)) $location = $this->yellow->config->get("wikiLocation");
			if(empty($pagesMax)) $pagesMax = $this->yellow->config->get("wikiPagesMax");
			$wiki = $this->yellow->pages->find($location);
			$pages = $this->getWikiPages($location);
			$pages->sort("title")->limit($pagesMax);
			$page->setLastModified($pages->getModified());
			if(count($pages))
			{
				$output = "<div class=\"".htmlspecialchars($name)."\">\n";
				$output .= "<ul>\n";
				foreach($pages as $page)
				{
					$output .= "<li><a href=\"".$page->getLocation(true)."\">".$page->getHtml("titleNavigation")."</a></li>\n";
				}
				$output .= "</ul>\n";
				$output .= "</div>\n";
			} else {
				$page->error(500, "Wikipages '$location' does not exist!");
			}
		}
		if($name=="wikirecent" && $shortcut)
		{
			list($location, $pagesMax) = $this->yellow->toolbox->getTextArgs($text);
			if(empty($location)) $location = $this->yellow->config->get("wikiLocation");
			if(empty($pagesMax)) $pagesMax = $this->yellow->config->get("wikiPagesMax");
			$wiki = $this->yellow->pages->find($location);
			$pages = $this->getWikiPages($location);
			$pages->sort("modified", false)->limit($pagesMax);
			$page->setLastModified($pages->getModified());
			if(count($pages))
			{
				$output = "<div class=\"".htmlspecialchars($name)."\">\n";
				$output .= "<ul>\n";
				foreach($pages as $page)
				{
					$output .= "<li><a href=\"".$page->getLocation(true)."\">".$page->getHtml("titleNavigation")."</a></li>\n";
				}
				$output .= "</ul>\n";
				$output .= "</div>\n";
			} else {
				$page->error(500, "Wikirecent '$location' does not exist!");
			}
		}
		if($name=="wikirelated" && $shortcut)
		{
			list($location, $pagesMax) = $this->yellow->toolbox->getTextArgs($text);
			if(empty($location)) $location = $this->yellow->config->get("wikiLocation");
			if(empty($pagesMax)) $pagesMax = $this->yellow->config->get("wikiPagesMax");
			$wiki = $this->yellow->pages->find($location);
			$pages = $this->getWikiPages($location);
			$pages->similar($page->getPage("main"))->limit($pagesMax);
			$page->setLastModified($pages->getModified());
			if(count($pages))
			{
				$output = "<div class=\"".htmlspecialchars($name)."\">\n";
				$output .= "<ul>\n";
				foreach($pages as $page)
				{
					$output .= "<li><a href=\"".$page->getLocation(true)."\">".$page->getHtml("titleNavigation")."</a></li>\n";
				}
				$output .= "</ul>\n";
				$output .= "</div>\n";
			} else {
				$page->error(500, "Wikirelated '$location' does not exist!");
			}
		}
		if($name=="wikitags" && $shortcut)
		{
			list($location, $pagesMax) = $this->yellow->toolbox->getTextArgs($text);
			if(empty($location)) $location = $this->yellow->config->get("wikiLocation");
			if(empty($pagesMax)) $pagesMax = $this->yellow->config->get("wikiPagesMax");
			$wiki = $this->yellow->pages->find($location);
			$pages = $this->getWikiPages($location);
			$page->setLastModified($pages->getModified());
			$tags = array();
			foreach($pages as $page) if($page->isExisting("tag")) foreach(preg_split("/\s*,\s*/", $page->get("tag")) as $tag) ++$tags[$tag];
			if(count($tags))
			{
				$tags = $this->yellow->lookup->normaliseUpperLower($tags);
				if($pagesMax!=0 && count($tags)>$pagesMax)
				{
					uasort($tags, strnatcasecmp);
					$tags = array_slice($tags, -$pagesMax);
				}				
				uksort($tags, strnatcasecmp);
				$output = "<div class=\"".htmlspecialchars($name)."\">\n";
				$output .= "<ul>\n";
				foreach($tags as $key=>$value)
				{
					$output .= "<li><a href=\"".$wiki->getLocation(true).$this->yellow->toolbox->normaliseArgs("tag:$key")."\">";
					$output .= htmlspecialchars($key)."</a></li>\n";
				}
				$output .= "</ul>\n";
				$output .= "</div>\n";
			} else {
				$page->error(500, "Wikitags '$location' does not exist!");
			}
		}
		return $output;
	}
	
	// Handle page parsing
	function onParsePage()
	{
		if($this->yellow->page->get("template")=="wikipages")
		{
			$pages = $this->getWikiPages($this->yellow->page->location);
			$pagesFilter = array();
			if($_REQUEST["special"]=="pages")
			{
				array_push($pagesFilter, $this->yellow->text->get("wikiSpecialPages"));
			}
			if($_REQUEST["special"]=="changes")
			{
				$chronologicalOrder = true;
				array_push($pagesFilter, $this->yellow->text->get("wikiSpecialChanges"));
			}
			if($_REQUEST["tag"])
			{
				$pages->filter("tag", $_REQUEST["tag"]);
				array_push($pagesFilter, $pages->getFilter());
			}
			if($_REQUEST["author"])
			{
				$pages->filter("author", $_REQUEST["author"], false);
				array_push($pagesFilter, $pages->getFilter());
			}
			if($_REQUEST["modified"])
			{
				$pages->filter("modified", $_REQUEST["modified"], false);
				array_push($pagesFilter, $this->yellow->text->normaliseDate($pages->getFilter()));
			}
			$pages->sort($chronologicalOrder ? "modified" : "title", $chronologicalOrder);
			$pages->pagination($this->yellow->config->get("wikiPaginationLimit"));
			if(!$pages->getPaginationNumber()) $this->yellow->page->error(404);
			if(!empty($pagesFilter))
			{
				$title = implode(' ', $pagesFilter);
				$this->yellow->page->set("titleHeader", $title." - ".$this->yellow->page->get("sitename"));
				$this->yellow->page->set("titleWiki", $this->yellow->text->get("wikiFilter")." ".$title);
				$this->yellow->page->set("wikipagesChronologicalOrder", $chronologicalOrder);
			}
			$this->yellow->page->set("content", !empty($pagesFilter) ? "content-wikipages" : "content-wiki");
			$this->yellow->page->setPages($pages);
			$this->yellow->page->setLastModified($pages->getModified());
			$this->yellow->page->setHeader("Cache-Control", "max-age=60");
		}
		if($this->yellow->page->get("template")=="wiki")
		{
			$location = $this->yellow->config->get("wikiLocation");
			if(empty($location)) $location = $this->yellow->lookup->getDirectoryLocation($this->yellow->page->location);
			$wiki = $this->yellow->pages->find($location);
			$this->yellow->page->setPage("wiki", $wiki);
		}
	}
	
	// Handle content file editing
	function onEditContentFile($page, $action)
	{
		if($page->get("template")=="wiki") $page->set("pageNewLocation", $this->yellow->config->get("wikiNewLocation"));
	}
	
	// Return wiki pages
	function getWikiPages($location)
	{
		$pages = $this->yellow->pages->clean();
		$wiki = $this->yellow->pages->find($location);
		if($wiki)
		{
			if($location==$this->yellow->config->get("wikiLocation"))
			{
				$pages = $this->yellow->pages->index(!$wiki->isVisible());
			} else {
				$pages = $wiki->getChildren(!$wiki->isVisible());
			}
			$pages->filter("template", "wiki")->append($wiki);
		}
		return $pages;
	}
}

$yellow->plugins->register("wiki", "YellowWiki", YellowWiki::VERSION);
?>
