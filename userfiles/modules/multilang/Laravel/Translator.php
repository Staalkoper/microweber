<?php namespace Multilanguage;

use DB;

class Translator {

	private $table = 'translations';

	public $translatableTables;

	public function __construct()
	{
		// TODO: no hardcoding
		$this->translatableTables = [
			'content' => ['title', 'content'],
			'content_fields' => ['value']
		];
	}

	/* @return: whether the translation has been stored
	*/
	public function store($sql, $bindings)
	{
		$lang = app()->getLocale();
		
		if($lang == config('app.fallback_locale')) return false;

		$parser = app('mw.translator.sqlparser')->parse($sql);
		
		$table = $parser->getTable();

		if (!in_array($table, array_keys($this->translatableTables))) return false;

		$id = null;
		$newTranslation = array();

		foreach ($parser->parsed['SET'] as $s => $setField)
		{
			$fieldName = substr($setField['sub_tree'][0]['base_expr'], 1, -1);
			
			if (in_array($fieldName, $this->translatableTables[$table])) {
				$newTranslation[$fieldName] = $bindings[$s];
			} else if($fieldName == 'id') {
				$id = $bindings[$s];
			}
		}

		if(!$id) return false;

		$translation = DB::table($this->table)
			->where('translatable_id', $id)
			->where('translatable_type', $table)
			->where('lang', $lang)
			->first();

		if($translation)
		{
			$existingData = json_decode($translation->translation);
			if($existingData) {
				$newTranslation = (object) array_merge((array) $existingData, (array) $newTranslation);;
			}
		}

		$newTranslation = json_encode($newTranslation);
		if(!$newTranslation) $newTranslation = null;

		$success = false;

		if($translation)
		{
			$query = DB::table($this->table)->whereId($translation->id);

			if($newTranslation) {
				$success = (bool) $query->update(['translation' => $newTranslation]);
			}
			else {
				$success = (bool) $query->delete();
			}
		}
		else if($newTranslation)
		{
			$success = (bool) DB::table($this->table)->insert([
				'lang' => $lang,
				'translatable_type' => $table,
				'translatable_id' => $id,
				'translation' => $newTranslation
			]);
		}

		return $success;
	}
	
	/* @return: void
	*/
	public function translate($sql, &$results)
	{
		if(app()->getLocale() == config('app.fallback_locale')) return;

		$parser = app('mw.translator.sqlparser')->parse($sql);
		$table = $parser->getTable();
  
		if (!in_array($table, array_keys($this->translatableTables))) return;

		if(!$results || !count($results)) return;

		$ids = array_map(function($result) { if(isset($result->id)) return $result->id; }, $results);

		if (!count($ids)) return;

		$translations = DB::table($this->table)
			->whereTranslatableType($table)
			->whereIn('translatable_id', $ids)
			->get();
		$translations = collect($translations)->keyBy('translatable_id');

		$unsets = [];

		foreach ($results as $r => &$result)
		{
			if (!isset($result->id)) continue;
			if (!isset($translations[$result->id]))
			{
				/*if(get_option('mutilang_no_fallback', 'website')) {
					$unsets[] = &$result;
					continue;
				}
				else
				*/continue;
			}

			$trans = $translations[$result->id]->translation;
			$trans = json_decode($trans);
			if (!$trans) continue;

			foreach ($this->translatableTables[$table] as $field)
			{
				if(!isset($trans->$field)) continue;
				$result->$field = $trans->$field;
			}
		}

		if(count($unsets)) {
			foreach ($unsets as &$unset) {
				unset($unset);
			}
		}
	}

}

app()->singleton('mw.translator', function() {
  return new Translator;
});
