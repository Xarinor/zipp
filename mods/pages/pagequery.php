<?php
/*
@package: Zipp
@version: 0.2 <2019-07-04>
*/

namespace Pages;

use DateTime;
use Themes\Theme;
use function MagmaTemplate\build_ctnSorter;

class PageQuery {

	protected $pages = null;

	protected $theme = null;

	protected $layouts = null;

	protected $ids = null;

	protected $order = null;

	protected $amount = -1;

	public function __construct( Pages $pages, Theme $theme ) {
		$this->pages = $pages;
		$this->theme = $theme;
	}

	public function byLayouts( array $layouts ) {
		$this->layouts = $layouts;
		return $this;
	}

	public function byLayout( string $layout ) {
		$this->layouts = [$layout];
		return $this;
	}

	public function byIds( array $ids ) {
		$this->ids = $ids;
		return $this;
	}

	public function byId( int $id ) {
		$this->ids = [$id];
		return $this;
	}

	public function orderDesc( string $key ) {
		$this->order = [false, $key];
		return $this;
	}

	public function orderAsc( string $key ) {
		$this->order = [true, $key];
		return $this;
	}

	public function orderByCTN(string $field, string $order = null) {

		$pages = $this->go();

		foreach ( $pages as $p ) {
			$ly = $this->theme->resolvePage( $p );
			$p->replaceCtn( $ly->fillFields( $p ) );
		}

		if ($order == 'random') {
			shuffle($pages);
		} else if ($order == null || $order == 'ascending') {
			usort($pages, build_CTN_Sorter($field, true));
		} else if ($order == 'descending') {
			usort($pages, build_CTN_Sorter($field, false));
		}

		return $this->filterAmount( $pages );
	}

	public function limit( int $amount ) {
		$this->amount = $amount;
		return $this;
	}

	public function all() {
		$this->amount = -1;
		return $this;
	}

	public function one() {
		$this->amount = 1;
		return $this;
	}

	public function full() {

		$pages = $this->go();

		foreach ( $pages as $p ) {
			$ly = $this->theme->resolvePage( $p );
			$p->replaceCtn( $ly->fullFill( $p ) );
		}

		return $this->filterAmount( $pages );
	}

	public function short() {

		$pages = $this->go();

		foreach ( $pages as $p )
			$this->theme->completeUrlOnPage( $p );

		return $this->filterAmount( $pages );
	}

	public function query() {

		$pages = $this->go();

		foreach ( $pages as $p ) {
			$ly = $this->theme->resolvePage( $p );
			$p->replaceCtn( $ly->fillFields( $p ) );
		}

		return $this->filterAmount( $pages );
	}

	protected function go() {
		return $this->pages->executeQuery( $this->ids, $this->layouts, $this->order, $this->amount );
	}

	protected function filterAmount( array $pages ) {
		return $this->amount === 1 ? ( $pages[0] ?? null ) : $pages;
	}

	// full

	// short

	// query

}

function build_CTN_Sorter($key, $flip) {
	return function ($inA, $inB) use ($key, $flip) {

		if ($inA->$key instanceof DateTime) {
			$outA = $inA->$key->format("U");
		} else if ($inA == '') {
			$outA = -1;
		} else if (is_string($inA->$key)) {
			$outA = $inA->$key;
		} else if (!is_string($inA->$key) && is_string(strval($inA->$key))) {
			$outA = strval($inA->$key);
		} else {
			$outA = 0;
		}
		if ($inB->$key instanceof DateTime) {
			$outB = $inB->$key->format("U");
		} else if ($inB == '') {
			$outB = -1;
		} else if (is_string($inB->$key)) {
			$outB = $inB->$key;
		} else if (!is_string($inB->$key) && is_string(strval($inB->$key))) {
			$outB = strval($inB->$key);
		} else {
			$outB = 0;
		}

		$result = strnatcmp($outA, $outB);
		$result = $flip ? ($result * -1) : $result;
		return $result;
	};
}