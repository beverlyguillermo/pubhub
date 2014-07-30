<?php

namespace email\data;

class AnnouncementGetter extends SectionGetter
{
	protected $title = "Announcements";

	protected function getData()
	{
		return $this->get("announcements", array(
			"per_page" => -1,
			"email_publish_date" => date("Y-m-d", time())
		));
	}

	protected function formatItem($item)
	{
		$headline = !empty($item->alt_headline) ? $item->alt_headline : $item->headline;
		
		$html = "<p><strong><a href=\"{$item->url}\" style=\"font-weight: bold;\">{$headline}</a></strong><br />";
		$html .= !empty($item->excerpt) ? $item->excerpt : $item->body;
		$html .= "</p>";

		return $html;
	}
}