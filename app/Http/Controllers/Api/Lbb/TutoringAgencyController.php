<?php

namespace App\Http\Controllers\Api\Lbb;

use App\Model\Category;
use App\Model\SubCategory;
use App\Model\TutoringAgency;
use App\Model\Term;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PreProcessingController as PreProcessing;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class TutoringAgencyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($token)
    {
        $tutoring_agency = TutoringAgency::find(JWTAuth::authenticate($token)->tutoring_agency_id);

        foreach ($tutoring_agency->category_id as $categories) {
            $category = Category::find($categories, ['category']);
            $category_array [] = $category->category;
        }

        foreach ($tutoring_agency->sub_category_id as $sub_categories) {
            $sub_category = SubCategory::find($sub_categories, ['sub_category']);
            $sub_category_array [] = $sub_category->sub_category;
        }

        $tutoring_agency->category_id = $category_array;
        $tutoring_agency->sub_category_id = $sub_category_array;

        $response = [
            'msg' => 'Data Tutoring Agency',
            'data' => [
                'profil' => $tutoring_agency,
            ]
        ];
        return response()->json($response, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($token)
    {
        $tutoring_agency = TutoringAgency::find(JWTAuth::authenticate($token)->tutoring_agency_id);
        $category = Category::all(['id', 'category']);
        $sub_category = SubCategory::all(['id', 'sub_category']);
        $response = [
            'msg' => 'Edit Data Tutoring Agency',
            'data' => [
                'profil' => $tutoring_agency,
                'category' => $category,
                'sub_category' => $sub_category,
            ]
        ];
        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $token)
    {
        $this->validate($request, [
            'category_id' => 'required',
            'sub_category_id' => 'required',
            'tutoring_agency' => 'required|max:255',
            'logo_image' => 'image|mimes:jpeg,jpg,png|max:500',
            'address' => 'required|max:255',
            'description' => 'required|max:500',
            'tags' => 'max:255',
        ]);

        $tutoring_agency = TutoringAgency::find(JWTAuth::authenticate($token)->tutoring_agency_id);

        $logo_image = $tutoring_agency->logo_image;
        if ($request->hasFile('logo_image')) {
            if ($tutoring_agency->logo_image != "upload/logo/default-logo.png") {
                unlink(public_path($tutoring_agency->logo_image));
            }
            $logo_image = 'upload/logo/' . str_slug($request->tutoring_agency) . '.' . $request->logo_image->getClientOriginalExtension();
            $request->logo_image->move(public_path('upload/logo/'), $logo_image);
        }

        $tutoring_agency->update([
            'category_id' => $request->category_id,
            'sub_category_id' => $request->sub_category_id,
            'slug' => str_slug($request->tutoring_agency),
            'tutoring_agency' => $request->tutoring_agency,
            'logo_image' => $logo_image,
            'address' => $request->address,
            'description' => $request->description,
            'tags' => array_filter(array_unique(explode(",", $request->tags))),
        ]);

        $this->createTerm($tutoring_agency->id);

        return response()->json(['msg' => 'Berhasil Update !'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function shortProfile($token)
    {
        $tutoring_agency = TutoringAgency::find(JWTAuth::authenticate($token)->tutoring_agency_id, ['tutoring_agency','verified']);
        $response = [
            'msg' => 'Data Lembaga',
            'data' => [
                'profil' => $tutoring_agency,
            ]
        ];
        return response()->json($response, 200);
    }

    private function createTerm($tutoring_agency_id)
    {
        $term = TutoringAgency::select('description','tags')->where('id', $tutoring_agency_id)->first();
        $term = implode(' ', $term->tags) . " " . $term->description;

        $term = PreProcessing::stemming($term);

        return Term::where('lbb_id', $tutoring_agency_id)->update(['term' => $term]);
    }
}
