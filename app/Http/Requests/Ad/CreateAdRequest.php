<?php

namespace App\Http\Requests\Ad;

use App\DataProviders\OptionDataProvider;
use App\Models\Advertisement;
use Illuminate\Foundation\Http\FormRequest;
use Nwidart\Modules\Facades\Module;

class CreateAdRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|max:255',
            // 'redirect_url' => 'url',
            'description' => 'max:500',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ];
    }
    private function getModuleThemeAds()
    {
        $modules = Module::allEnabled();
        $moduleThemes = collect();
        foreach ($modules as $module) {
            $moduleThemeConfig = $module->getPath() . '/module-ext.json';
            if (file_exists($moduleThemeConfig)) {
                $moduleThemeConfigJson = file_get_contents($moduleThemeConfig);
                $moduleThemeConfigData = json_decode($moduleThemeConfigJson);
                // Check validate config file
                if (
                    isset($moduleThemeConfigData->type)
                    && $moduleThemeConfigData->type == 'frontpage'
                    && isset($moduleThemeConfigData->ads)
                    && $moduleThemeConfigData->ads
                ) {
                    $module->ext = $moduleThemeConfigData;
                    $moduleThemes->push($module);
                }
            }
        }
        return $moduleThemes;
    }
    public function withValidator($validator)
    {
        // $validator->after(function ($validator) {
        //     $error = json_decode($this->hasConflict());
        //     if ($error!=null) {
        //         $moduleThemes = $this->getModuleThemeAds();
        //         $adName_='';
        //         foreach ($moduleThemes as $module) {
        //             $breakLoop = false;
        //             foreach ($module->ext->ads as $adKey => $adName) {
        //                 if ($adKey === $error->posititon) {
        //                     $adName_=$adName;
        //                     $breakLoop = true;
        //                     break;
        //                 }
        //             }
        //             if ($breakLoop) {
        //                 break;
        //             }
        //          }
        //         $validator->errors()->add('position',__('Vị trí').' '.$adName_.' '.__('đã có quảng cáo từ: ').$error->start_date.' - '.$error->end_date );
        //     }
        // });
    }
    protected function hasConflict()
    {
    //     //Lấy thông tin các vị trí quảng cáo
        // $positions = [];
        //     // Vị trí menu
        //     if (isset($this->position) && $this->position) {
        //         foreach ($this->position as $position => $enable) {
        //             if ($enable) {
        //                 array_push($positions,$position);
        //             }
        //       
        //     
       
                $ad = Advertisement::where('position', $this->position)
                ->where(function ($query) {
                     $query->whereBetween('start_date', [$this->start_date, $this->end_date])
                        ->orWhereBetween('end_date', [$this->start_date, $this->end_date])
                        ->orWhere(function ($query) {
                            $query->where('start_date', '<=', $this->start_date)
                                ->where('end_date', '>=', $this->end_date);
                        });
                   })->where('id','<>',$this->id)->first();
                if($ad!=null)
                {
                    $jsonData = array(
                        'posititon'=> $this->position,
                        'start_date'=>$ad->start_date->format('H:i d/m/Y') ,
                        'end_date'=>$ad->end_date->format('H:i d/m/Y') 
                    );
                    return json_encode($jsonData);
                }
          
               
            return null;
            

    }

    public function attributes(): array
    {
        return [
          'title' => __('Tiêu đề quảng cáo'),
          'description' => __('Mô tả')
        ];
    }
}
