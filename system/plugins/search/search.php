<?php
// Search plugin, https://github.com/datenstrom/yellow-plugins/tree/master/search
// Copyright (c) 2013-2017 Datenstrom, https://datenstrom.se
// This file may be used and distributed under the terms of the public license.

class YellowSearch
{
	const VERSION = "0.6.12";
	var $yellow;			//access to API
	
	// Handle initialisation
	function onLoad($yellow)
	{
		$this->yellow = $yellow;
		$this->yellow->config->setDefault("searchLocation", "/search/");
		$this->yellow->config->setDefault("searchPaginationLimit", "5");
		$this->yellow->config->setDefault("searchPageLength", "250");
	}
	
	// Handle page content parsing of custom block
	function onParseContentBlock($page, $name, $text, $shortcut)
	{
		$output = null;
		if($name=="search" && $shortcut)
		{
			list($location) = $this->yellow->toolbox->getTextArgs($text);
			if(empty($location)) $location = $this->yellow->config->get("searchLocation");
			$output = "<div class=\"".htmlspecialchars($name)."\">\n";
			$output .= "<form class=\"search-form\" action=\"".$this->yellow->page->base.$location."\" method=\"post\">\n";
			$output .= "<input class=\"form-control\" type=\"text\" name=\"query\" placeholder=\"".$this->yellow->text->getHtml("searchButton")."\" />\n";
			$output .= "<input type=\"hidden\" name=\"clean-url\" />\n";
			$output .= "</form>\n";
			$output .= "</div>\n";
		}
		return $output;
	}
	
	// Handle page parsing
	function onParsePage()
	{
		if($this->yellow->page->get("template")=="search")
		{
			if($this->yellow->isCommandLine()) $this->yellow->page->error(500, "Static website not supported!");
			$query = trim($_REQUEST["query"]);
			list($tokens, $filters) = $this->getSearchInformation($query, 10);
			if(!empty($tokens) || !empty($filters))
			{
				$pages = $this->yellow->pages->clean();
				$showInvisible = $filters["status"]=="draft" && $this->yellow->getRequestHandler()!="core";
				foreach($this->yellow->pages->index($showInvisible, false) as $page)
				{
					$searchScore = 0;
					$searchTokens = array();
					foreach($tokens as $token)
					{
						$score = substr_count(strtoloweru($page->getContent(true)), strtoloweru($token));
						if($score) { $searchScore += $score; $searchTokens[$token] = true; }
						if(stristr($page->getLocation(true), $token)) { $searchScore += 20; $searchTokens[$token] = true; }
						if(stristr($page->get("title"), $token)) { $searchScore += 10; $searchTokens[$token] = true; }
						if(stristr($page->get("tag"), $token)) { $searchScore += 5; $searchTokens[$token] = true; }
						if(stristr($page->get("author"), $token)) { $searchScore += 2; $searchTokens[$token] = true; }
					}
					if(count($tokens)==count($searchTokens))
					{
						$page->set("searchscore", $searchScore);
						$pages->append($page);
					}
				}
				if(!empty($filters))
				{
					if($filters["tag"]) $pages->filter("tag", $filters["tag"]);
					if($filters["author"]) $pages->filter("author", $filters["author"]);
					if($filters["language"]) $pages->filter("language", $filters["language"]);
					if($filters["status"]) $pages->filter("status", $filters["status"]);
				}
				$pages->sort("modified")->sort("searchscore");
				$pages->pagination($this->yellow->config->get("searchPaginationLimit"));
				if($_REQUEST["page"] && !$pages->getPaginationNumber()) $this->yellow->page->error(404);
				$title = empty($query) ? $this->yellow->text->get("searchSpecialChanges") : $query;
				$this->yellow->page->set("titleHeader", $title." - ".$this->yellow->page->get("sitename"));
				$this->yellow->page->set("titleSearch", $this->yellow->text->get("searchQuery")." ".$title);
				$this->yellow->page->setPages($pages);
				$this->yellow->page->setLastModified($pages->getModified());
				$this->yellow->page->setHeader("Cache-Control", "max-age=60");
				$this->yellow->page->set("status", count($pages) ? "done" : "empty");
			} else {
				$this->yellow->page->set("titleSearch", $this->yellow->page->get("title"));
				$this->yellow->page->set("status", "none");
			}
		}
	}
		
	// Return search information
	function getSearchInformation($query, $tokensMax)
	{
		$tokens = array_unique(array_filter($this->yellow->toolbox->getTextArgs($query), "strlen"));
		$filters = array_filter($_REQUEST, "strlen");
		foreach($tokens as $key=>$value)
		{
			preg_match("/^(.*?):(.*)$/", $value, $matches);
			if(!empty($matches[1]) && !strempty($matches[2]))
			{
				$filtersInQuery = true;
				$filters[$matches[1]] = $matches[2];
				unset($tokens[$key]);
			}
		}
		if($tokensMax) $tokens = array_slice($tokens, 0, $tokensMax);
		if(empty($tokens) && !$filtersInQuery && !$filters["special"]) $filters = array();
		return array($tokens, $filters);
	}
}

$yellow->plugins->register("search", "YellowSearch", YellowSearch::VERSION);
?>
