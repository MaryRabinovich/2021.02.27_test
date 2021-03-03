<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use App\Models\Substance;
use Illuminate\Http\Request;
use App\Http\Resources\Drug\ExactMatchResource;
use App\Http\Resources\Drug\PartialMatchResource;

class SearchDrugsController extends Controller
{
    public function search(Request $request)
    {
        $perPage = 5;

        /** 
         * get start data
         */ 
        $page = isset($request->page) ? (int) $request->page : false;
        $substancesInput = isset($request->substances) ? $request->substances : [];

        /**
         * simple answer when start data is unsufficient
         */
        if (count($substancesInput) < 2) {
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

        /**
         * clean start data, left only visible substancesIDs
         */
        $substances = [];
        foreach ($substancesInput as $substance) {
            if (Substance::find($substance)->visible)
                array_push($substances, (int) $substance);
        }
        $count = count($substances);

        /**
         * simple answer if only 0 or 1 substances left
         */
        if ($count < 2) {
            return response()->json([
                'current_page' => 1,
                'total'        => 0,
                'per_page'     => $perPage,
                'data'         => []
            ]);
        }

        /**
         * get all visible drugs matching at least one substance
         */
        $visibleDrugsWithOneMatchAtLeast = Drug::visible()->hasAtLeastOne($substances)
            ->with('substances')->get();

        /**
         * check for exact matches
         */
        $exactMatches = $visibleDrugsWithOneMatchAtLeast
            ->filter(function($drug) use ($substances) { 
                return $drug->hasAll($substances) && $drug->hasOnly($substances);
            });

        /**
         * if exact matches exist give exact answer
         */
        $exactMatchesTotal = $exactMatches->count();
        if ($exactMatchesTotal) {
            return response()->json([
                'per_page' => $perPage,
                'total' => $exactMatchesTotal,
                'current_page' => $page,
                'data' => ExactMatchResource::collection(
                    $exactMatches->chunk($perPage)->all()[$page - 1]
                )
            ]);
        }

        /**
         * get drugs with 5, 4, 3 and 2 nonexact matches,
         * add number of matched substances to every drug 
         */
        $partialMatches = collect([]);
        for ($present = $count; 
            $present >= 2 && count($partialMatches) <= $page*$perPage; 
            $present--
        ) {
            $except = $count - $present;
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

        /**
         * give this partially matched answer if exist
         */
        $partialMatchesTotal = $partialMatches->count();
        if ($partialMatchesTotal) {
            return response()->json([
                'per_page' => $perPage,
                'total' => $partialMatchesTotal,
                'current_page' => $page,
                'data' => PartialMatchResource::collection(
                    $partialMatches->chunk($perPage)->all()[$page - 1]
                )
            ]);
        }
        
        return [];
    }
}
