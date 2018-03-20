<?php


elgg_ws_expose_function(
	"get.wireposttest",
	"get_wirepost_test",
	array(
		"user" => array('type' => 'string', 'required' => true),
		"guid" => array('type' => 'int', 'required' => true),
		"thread" => array('type' => 'int', 'required' => false, 'default' => 0),
		"lang" => array('type' => 'string', 'required' => false, 'default' => "en")
	),
	'Retrieves a wire post & all replies based on user id and wire post id',
	'POST',
	true,
	false
);



function get_wirepost_test($user, $guid, $thread, $lang)
{
	$user_entity = is_numeric($user) ? get_user($user) : (strpos($user, '@') !== false ? get_user_by_email($user)[0] : get_user_by_username($user));
	if (!$user_entity) {
		return "User was not found. Please try a different GUID, username, or email address";
	}
	if (!$user_entity instanceof ElggUser) {
		return "Invalid user. Please try a different GUID, username, or email address";
	}


	$entity = get_entity($guid);
	if (!$entity) {
		return "Wire was not found. Please try a different GUID";
	}
	if (!$entity instanceof ElggWire) {
		return "Invalid wire. Please try a different GUID";
	}

	if (!elgg_is_logged_in()) {
		login($user_entity);
	}

	$thread_id = $entity->wire_thread;

	if ($thread) {
		$all_wire_posts = elgg_list_entities_from_metadata(array(
			"metadata_name" => "wire_thread",
			"metadata_value" => $thread_id,
			"type" => "object",
			"subtype" => "thewire",
			"limit" => 0,
			"preload_owners" => true
		));
		$wire_posts = json_decode($all_wire_posts);

		foreach ($wire_posts as $wire_post) {
			$wire_post_obj = get_entity($wire_post->guid);
			$reshare = $wire_post_obj->getEntitiesFromRelationship(array("relationship" => "reshare", "limit" => 1))[0];
			$wire_attachements = elgg_get_entities_from_relationship(array(
				'relationship' => 'is_attachment',
				'relationship_guid' => $wire_post->guid,
				'inverse_relationship' => true,
				'limit' => 1
			));

			if ($wire_attachements){
				$wire_post->attachment->guid = $wire_attachements[0]->getGUID();
				$wire_post->attachment->name = $wire_attachements[0]->original_filename;
			}

			$url = "";
			if (!empty($reshare)) {
				$url = $reshare->getURL();
			}

			$text = "";
			if (!empty($reshare->title)) {
				$text = $reshare->title;
			} elseif (!empty($reshare->name)) {
				$text = $reshare->name;
			} elseif (!empty($reshare->description)) {
				$text = elgg_get_excerpt($reshare->description, 140);
			}

			$wire_post->shareURL = $url;
			$wire_post->shareText = gc_explode_translation($text, $lang);

			$likes = elgg_get_annotations(array(
				'guid' => $wire_post->guid,
				'annotation_name' => 'likes'
			));
			$wire_post->likes = count($likes);

			$liked = elgg_get_annotations(array(
				'guid' => $wire_post->guid,
				'annotation_owner_guid' => $user_entity->guid,
				'annotation_name' => 'likes'
			));
			$wire_post->liked = count($liked) > 0;

			$replied = elgg_get_entities_from_metadata(array(
				"metadata_name" => "wire_thread",
				"metadata_value" => $thread_id,
				"type" => "object",
				"subtype" => "thewire",
				"owner_guid" => $user_entity->guid
			));
			$wire_post->replied = count($replied) > 0;

			$wire_post->thread_id = $thread_id;

			$wire_post->userDetails = get_user_block($wire_post->owner_guid, $lang);
			$wire_post->description = wire_filter($wire_post->description);
		}
	} else {
		$wire_posts = elgg_list_entities(array(
			"type" => "object",
			"subtype" => "thewire",
			"guid" => $guid
		));
		$wire_post = json_decode($wire_posts)[0];

		$wire_post_obj = get_entity($wire_post->guid);
		$reshare = $wire_post_obj->getEntitiesFromRelationship(array("relationship" => "reshare", "limit" => 1))[0];
		$wire_attachements = elgg_get_entities_from_relationship(array(
			'relationship' => 'is_attachment',
			'relationship_guid' => $wire_post->guid,
			'inverse_relationship' => true,
			'limit' => 1
		));

		if ($wire_attachements){
			$wire_post->attachment->guid = $wire_attachements[0]->getGUID();
			$wire_post->attachment->name = $wire_attachements[0]->original_filename;
		}

		$url = "";
		if (!empty($reshare)) {
			$url = $reshare->getURL();
		}

		$text = "";
		if (!empty($reshare->title)) {
			$text = $reshare->title;
		} elseif (!empty($reshare->name)) {
			$text = $reshare->name;
		} elseif (!empty($reshare->description)) {
			$text = elgg_get_excerpt($reshare->description, 140);
		}

		$wire_post->shareURL = $url;
		$wire_post->shareText = gc_explode_translation($text, $lang);

		$likes = elgg_get_annotations(array(
			'guid' => $wire_post->guid,
			'annotation_name' => 'likes'
		));
		$wire_post->likes = count($likes);

		$liked = elgg_get_annotations(array(
			'guid' => $wire_post->guid,
			'annotation_owner_guid' => $user_entity->guid,
			'annotation_name' => 'likes'
		));
		$wire_post->liked = count($liked) > 0;

		$replied = elgg_get_entities_from_metadata(array(
			"metadata_name" => "wire_thread",
			"metadata_value" => $thread_id,
			"type" => "object",
			"subtype" => "thewire",
			"owner_guid" => $user_entity->guid
		));
		$wire_post->replied = count($replied) > 0;

		$wire_post->thread_id = $thread_id;

		$wire_post->userDetails = get_user_block($wire_post->owner_guid, $lang);
		$wire_post->description = wire_filter($wire_post->description);

		$wire_posts = $wire_post;
	}

	return $wire_posts;
}
