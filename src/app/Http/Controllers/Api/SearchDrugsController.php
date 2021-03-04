<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use App\Models\Substance;
use Illuminate\Http\Request;
use App\Http\Resources\Api\Drug\ExactMatchResource;
use App\Http\Resources\Api\Drug\PartialMatchResource;

class SearchDrugsController extends Controller
{
    protected $page, $perPage;

    public function __construct(Request $request)
    {
        $this->perPage = 5;
        $this->page = isset($request->page) ? (int) $request->page : 1;
        if ($this->page < 1 || $this->page > 10000) return $this->trollJson();
    }

    public function search(Request $request)
    {
        /** 
         * get start data
         */ 
        $substancesInput = isset($request->substances) ? $request->substances : [];

        /**
         * simple answer if start data is insufficient (0 or 1 element)
         */
        if (count($substancesInput) < 2) return $this->inputInsufficientJson();

        /**
         * clean start data, left only visible substancesIDs
         * simple answer if cleaned data is insufficient (0 or 1 left)
         */
        $substances = [];
        foreach ($substancesInput as $substance) {
            $isIt = Substance::find($substance);
            if ($isIt) {
                if ($isIt->visible) array_push($substances, (int) $substance);
            } else return $this->trollJson();
        }
        if (count($substances) < 2) return $this->visibleInputInsufficientJson();

        /**
         * get all visible drugs matching at least one substance
         */
        $visibleDrugsWithOneMatchAtLeast = Drug::visible()->hasAtLeastOne($substances)
            ->with('substances')->get();

        /**
         * check for exact matches
         * give exact answer if exists
         */
        $exactMatches = $visibleDrugsWithOneMatchAtLeast
            ->filter(function($drug) use ($substances) { 
                return $drug->hasAll($substances) && $drug->hasOnly($substances);
            });
        if ($exactMatches->count()) return $this->exactMatchesJson($exactMatches);

        /**
         * get drugs with 5, 4, 3 and 2 nonexact matches,
         * add number of matched substances to every drug 
         * give partially matched answer if exists
         */
        $partialMatches = collect([]);
        for ($present = count($substances); $present >= 2; $present--) {
            $except = count($substances) - $present;
            $nextPart = $visibleDrugsWithOneMatchAtLeast
                ->filter(function($drug) use ($substances, $except) {
                return $drug->hasAllBut($substances, $except);
            });
            $nextPart->transform(function($drug) use ($present) {
                $drug->append('isset_substances');
                $drug->isset_substances = $present;
                return $drug;
            });
            $partialMatches = $partialMatches->merge($nextPart);
        }
        if ($partialMatches->count()) return $this->partialMatchesJson($partialMatches);

        /**
         * simple answer for unchecked cases
         */
        return response()->json([]);
    }



    /**
     * formatted answers
     */
    public function trollJson()
    {
        return response()->json([
            'errors' => [
                'substances' => [
                    'ты зачем этим занимаешься'
                ]
            ]
        ]);
    }
    public function inputInsufficientJson()
    {
        return response()->json(
            [
                'errors'         => [
                    'substances' => [
                        'не ленись, добавь веществ'
                    ]
                ]
            ]
        );
    }
    public function visibleInputInsufficientJson()
    {
        return response()->json([
            'current_page' => 1,
            'total'        => 0,
            'per_page'     => $this->perPage,
            'data'         => []
        ]);
    }
    public function exactMatchesJson($collection)
    {
        return response()->json([
            'per_page' => $this->perPage,
            'total' => $collection->count(),
            'current_page' => $this->page,
            'data' => ExactMatchResource::collection(
                $collection->chunk($this->perPage)->all()[$this->page - 1]
            )
        ]);
    }
    public function partialMatchesJson($collection)
    {
        return response()->json([
            'per_page' => $this->perPage,
            'total' => $collection->count(),
            'current_page' => $this->page,
            'data' => PartialMatchResource::collection(
                $collection->chunk($this->perPage)->all()[$this->page - 1]
            )
        ]);
    }
}
