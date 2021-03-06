<?php

namespace App\Http\Controllers\Api\User;

use App\Model\TutoringAgency;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SearchController extends Controller
{
    public function doSearch(Request $request)
    {
        $search = preg_replace("/[^a-zA-Z\ ]/"," ",strtolower($request->search));
        $search = array_values(array_filter(explode(" ",$search)));

        $lembaga = TutoringAgency::select('id','slug','tutoring_agency','rating')
            ->where('tutoring_agency', 'REGEXP', implode('|', $search))
            ->get();

        if (count($lembaga) === 0){
            return response()->json(['msg' => 'Content Not Found'],204);
        }

        foreach ($lembaga as $row) {
            $tutoring_agency_id = TutoringAgency::find($row->id);
            $row->total_comments  = $tutoring_agency_id->feedback()->count();
        }

        $response = [
            'msg' => 'Data Lembaga Hasil Pencarian',
            'lembaga' => $lembaga
        ];

        return response()->json($response,200);
    }
}
