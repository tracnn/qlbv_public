<?php

namespace App\Http\Controllers\Vaccination;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\PreVaccinationCheck;
use App\Patient;
use App\Vaccine;
use DB;

class PreVaccinationCheckController extends Controller
{
    public function index()
    {
        $checks = PreVaccinationCheck::with('patient')->get();
        return view('vaccination.pre_vaccination_checks.index', compact('checks'));
    }

    public function create($patient_id)
    {
        $patient = Patient::find($patient_id);
        $vaccines = Vaccine::all(); // Lấy danh sách vắc xin
        $doctors = DB::connection('HISPro')->table('HIS_EMPLOYEE')
                         ->where('IS_DOCTOR', 1)
                         ->get(); //Lấy danh sách Bác sĩ

        return view('vaccination.pre_vaccination_checks.create', compact('patient', 'vaccines', 'doctors'));
    }

    public function store(Request $request, $patient_id)
    {
        $request->validate([
            'vaccine_id' => 'required|exists:vaccines,id',
            'time' => 'required|date_format:Y-m-d\TH:i',
            'weight' => 'nullable|string|max:255',
            'temperature' => 'nullable|string|max:255',
            'anaphylactic_reaction' => 'required|boolean',
            'acute_or_chronic_disease' => 'required|boolean',
            'corticosteroids' => 'required|boolean',
            'fever_or_hypothermia' => 'required|boolean',
            'immune_deficiency' => 'required|boolean',
            'abnormal_heart' => 'required|boolean',
            'abnormal_lungs' => 'required|boolean',
            'abnormal_consciousness' => 'required|boolean',
            'other_contraindications' => 'nullable|string',
            'specialist_exam' => 'required|boolean',
            'specialist_exam_details' => 'nullable|string',
            'eligible_for_vaccination' => 'required|boolean',
            'contraindication' => 'required|boolean',
            'postponed' => 'required|boolean',
            'administered_by' => 'required|string|max:255',
        ]);

        $preVaccinationCheck = new PreVaccinationCheck($request->all());
        $preVaccinationCheck->patient_id = $patient_id;
        $preVaccinationCheck->save();

        return redirect()->route('vaccination.data', ['patient_code' => $request->patient_code])->with('success', 'Thông tin khám đã được thêm.');
    }

    public function show($id)
    {
        $check = PreVaccinationCheck::find($id);
        return view('vaccination.pre_vaccination_checks.show', compact('check'));
    }

    public function edit($id)
    {
        $check = PreVaccinationCheck::find($id);

        $vaccines = Vaccine::all();
        $doctors = DB::connection('HISPro')->table('HIS_EMPLOYEE')
                         ->where('IS_DOCTOR', 1)
                         ->get(); //Lấy danh sách Bác sĩ

        return view('vaccination.pre_vaccination_checks.edit', compact('check', 'vaccines', 'doctors'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'vaccine_id' => 'required|exists:vaccines,id',
            'time' => 'required|date_format:Y-m-d\TH:i',
            'weight' => 'nullable|string|max:255',
            'temperature' => 'nullable|string|max:255',
            'anaphylactic_reaction' => 'required|boolean',
            'acute_or_chronic_disease' => 'required|boolean',
            'corticosteroids' => 'required|boolean',
            'fever_or_hypothermia' => 'required|boolean',
            'immune_deficiency' => 'required|boolean',
            'abnormal_heart' => 'required|boolean',
            'abnormal_lungs' => 'required|boolean',
            'abnormal_consciousness' => 'required|boolean',
            'other_contraindications' => 'nullable|string',
            'specialist_exam' => 'required|boolean',
            'specialist_exam_details' => 'nullable|string',
            'eligible_for_vaccination' => 'required|boolean',
            'contraindication' => 'required|boolean',
            'postponed' => 'required|boolean',
            'administered_by' => 'required|string|max:255',
        ]);


        $check = PreVaccinationCheck::find($id);
        $check->update($request->all());

        return redirect()->route('vaccination.data', ['patient_code' => $request->patient_code])
        ->with('success', 'Thông tin khám đã được thêm.');
    }

    public function destroy($id)
    {
        $prevaccination = PreVaccinationCheck::find($id);
        $prevaccination->delete();

        return redirect()->route('vaccination.data', ['patient_code' => $prevaccination->patient->code])
        ->with('success', 'Thông tin tiêm chủng đã được xóa.');
    }
}
