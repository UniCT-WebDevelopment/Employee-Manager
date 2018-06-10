<?php
/**
 * Autobot project.
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Dipendente;
use App\Models\Permessi;
use App\Models\Sottoposti;
use Carbon\Carbon;
use App\angularproject\CommonFunction;
use Hash;
/**
 *
 *@deprecated See WebHookVotaController
 */
class GestionePermessiController extends Controller
{
    public function __construct()
    {
        //$this->middleware('jwt.auth');
    }

    public function getPermessiEnumArray(){
        return response()->json(['success' => true, 'data'=> Permessi::getPermessiEnumArray()]);
    }

    public function cancellaPermesso(Request $request){
        $params = $request->only('token', 'id_permesso');
        $user = CommonFunction::tokenToDipendente($params['token']);

        if(empty($user)){
            return response()->json(['success' => false, 'error' => 'Invalid token']);
        }
        $rawPermessi = Permessi::where('id', '=', $params['id_permesso'])->where('id_dipendente', '=', $user->id_dipendente)->delete();
        return response()->json(['success' => true, 'data' => 'Permesso eliminato']);
    }

    public function getListaPermessiDipendente(Request $request){
        $params = $request->only('token');
        $user = CommonFunction::tokenToDipendente($params['token']);
        if(empty($user)){
            return response()->json(['success' => false, 'error' => 'Invalid token']);
        }

        $rawPermessi = Permessi::where('id_dipendente', '=', $user->id_dipendente)->get();
        $fixedPermessi = [];
        foreach($rawPermessi as $permesso){
            $data_inizio = new Carbon($permesso->data_inizio);
            $data_fine = new Carbon($permesso->data_fine);
            $element = [
                'id' => $permesso->id,
                'data_inizio' => $data_inizio->format('d-m-Y'),
                'data_fine' => $data_fine->format('d-m-Y'),
                'totale_giorni' => $data_inizio->diffInDays($data_fine, false),
                'stato_richiesta' => $permesso->stato,
                'certificatoBase64' => $permesso->certificatoBase64
            ];
            array_push($fixedPermessi, $element);
        }
        return response()->json(['success' => true, 'data' => $fixedPermessi]);
    }

    public function registraPermesso(Request $request){
        $params = $request->all();
        $dataInizio = Carbon::createFromDate(
            $params['dataInizio']['year'], 
            $params['dataInizio']['month'],
            $params['dataInizio']['day']
        );  
        $dataFine = Carbon::createFromDate(
            $params['dataFine']['year'], 
            $params['dataFine']['month'],
            $params['dataFine']['day']
        );  
        $user = CommonFunction::tokenToDipendente($params['token']);
        
        if(empty($user)){
            return response()->json(['success' => false, 'error' => 'Invalid token']);
        }else if($dataInizio > $dataFine){
            return response()->json(['success' => false, 'error' => 'Form non compilato correttamente, verifica i campi relativi alle date!']);
        }
        else{
            $soab = Carbon::now()->endOfDay()->toDateTimeString();
            $soab2 = Carbon::now()->toDateTimeString();
            $permesso = [
                'id_dipendente' => $user->id_dipendente,
                'data_inizio' => $dataInizio,
                'data_fine' => $dataFine,
                'note' => $params['note'],
                'tipologia' => $params['tipologia'],
                'stato' => 'pending',
                'certificatoBase64' => $params['certificatoBase64'],
            ];
            Permessi::create($permesso);
            return response()->json(['success' => true, 'data' => 'Permesso registrato']);
        }




        if($validator->fails()) {
            return response()->json(['success'=> false, 'error'=> $validator->messages()]);
        }
        
        $dipendente = Dipendente::create([
            'nome' => $credentials['nome'], 
            'cognome' => $credentials['cognome'], 
            'email' => $credentials['email'], 
            'codice_fiscale' => $credentials['codiceFiscale'], 
            'data_nascita' => $credentials['dataDiNascita'], 
            'password' => Hash::make($credentials['password']),
            'is_verified' => 1
        ]);
    }


//deprecati

    public function getListaRichiesteDipendente(Request $request){
        $user = CommonFunction::tokenToDipendente($request->get('token'));
        if(empty($user)){
            return CommonFunction::genericUnauthorizedAccess();
        }
        $permessi = Permessi::where('id_dipendente', '=', $user->id)->where('stato', '!=', 'approvato')->get()->toArray();
        return response()->json(['success' => true, 'message' => json_encode($permessi)]);
    }

    public function generateMockData(Request $request)
    {
        //endpoint
        //http://localhost/angularproject/be/generateMockData

        //Empty DB
        DB::statement("SET foreign_key_checks=0");
        Dipendente::truncate();
        Sottoposti::truncate();
        Permessi::truncate();
        DB::statement("SET foreign_key_checks=1");
        
        //Fill DB
        $mockList = [
            [
                'nome' => 'Orazio',
                'cognome' => 'Contarino',
                'email' => 'ocontarino@gmail.com',
                'ruolo' => 'manager',
                'codice_fiscale' => 'cntrzo94p28c351t', 
                'data_nascita' => Carbon::now(), 
                'password' => Hash::make('orazio'),
                'is_verified' => 1
            ],
            [
                'nome' => 'Giovanni',
                'cognome' => 'Longo',
                'email' => 'aaa@gmail.com',
                'ruolo' => 'manager',
                'codice_fiscale' => 'cntrzo94p28c352t', 
                'data_nascita' => Carbon::now(), 
                'password' => Hash::make('orazio'),
                'is_verified' => 1
            ],
            [
                'nome' => 'Mario',
                'cognome' => 'Rossi',
                'email' => 'bbb@gmail.com',
                'ruolo' => 'dipendente',
                'codice_fiscale' => 'cntrzo94p28c353t', 
                'data_nascita' => Carbon::now(), 
                'password' => Hash::make('orazio'),
                'is_verified' => 1
            ],
            [
                'nome' => 'Turi',
                'cognome' => 'Bianchi',
                'email' => 'ccc@gmail.com',
                'ruolo' => 'dipendente',
                'codice_fiscale' => 'cntrzo94p28c354t', 
                'data_nascita' => Carbon::now(), 
                'password' => Hash::make('orazio'),
                'is_verified' => 1
            ],
            [
                'nome' => 'Carmelo',
                'cognome' => 'Verdi',
                'email' => 'ddd@gmail.com',
                'ruolo' => 'dipendente',
                'codice_fiscale' => 'cntrzo94p28c355t', 
                'data_nascita' => Carbon::now(), 
                'password' => Hash::make('orazio'),
                'is_verified' => 1
            ],
        ];

        $manager_id = null;
        $random_dipendente_id = null;
        foreach($mockList as $key => $value){
            $element = Dipendente::create($value);
            if($value['nome']=='Orazio'){
                $manager_id = $element->id_dipendente;
            }else if($value['nome']!='Giovanni'){
                Sottoposti::create([
                    'id_capo' => $manager_id,
                    'id_dipendente' => $element->id_dipendente,
                ]);
                Permessi::create([
                    'id_dipendente' => $element->id_dipendente,
                    'data_inizio' => Carbon::now(),
                    'data_fine' => Carbon::tomorrow(),
                    'note' => 'Nota di prova',
                    'tipologia' => 'ferie',
                    'stato' => 'approvato',
                    'certificatoBase64' => 'x'
                ]); 
                
                Permessi::create([
                    'id_dipendente' => $element->id_dipendente,
                    'data_inizio' => Carbon::tomorrow(),
                    'data_fine' => Carbon::tomorrow(),
                    'note' => 'Nota di prova',
                    'tipologia' => 'ferie',
                    'stato' => 'rifiutato',
                    'certificatoBase64' => 'x'
                ]); 

                Permessi::create([
                    'id_dipendente' => $element->id_dipendente,
                    'data_inizio' => Carbon::yesterday(),
                    'data_fine' => Carbon::tomorrow(),
                    'note' => 'Nota di prova',
                    'tipologia' => 'ferie',
                    'stato' => 'pending',
                    'certificatoBase64' => 'x'
                ]); 

                Permessi::create([
                    'id_dipendente' => $element->id_dipendente,
                    'data_inizio' => Carbon::now(),
                    'data_fine' => Carbon::tomorrow(),
                    'note' => 'Nota di prova',
                    'tipologia' => 'malattia',
                    'stato' => 'approvato',
                    'certificatoBase64' => 'x'
                ]); 
                
                Permessi::create([
                    'id_dipendente' => $element->id_dipendente,
                    'data_inizio' => Carbon::tomorrow(),
                    'data_fine' => Carbon::tomorrow(),
                    'note' => 'Nota di prova',
                    'tipologia' => 'malattia',
                    'stato' => 'rifiutato',
                    'certificatoBase64' => 'x'
                ]); 

                Permessi::create([
                    'id_dipendente' => $element->id_dipendente,
                    'data_inizio' => Carbon::yesterday(),
                    'data_fine' => Carbon::tomorrow(),
                    'note' => 'Nota di prova',
                    'tipologia' => 'malattia',
                    'stato' => 'pending',
                    'certificatoBase64' => 'x'
                ]); 
            }
        }

    }

    public function selectSottoposti(Request $request){
        $managers_list = Dipendente::where('ruolo', '=', 'manager')->get();
        $json_response = [];
        foreach($managers_list as $element){
            Log::debug("Nome: ".$element->nome." cognome: ".$element->cognome);
            $sottoposti_list = Dipendente::where("id_dipendente", "=", $element->id_dipendente)->with('sottoposti.dipendente')->get()->toArray();
            Log::debug(print_r($sottoposti_list,true));
        }
        //Logs in angularproject/BEAngularProject/storage/logs/laravel.log
        return response("Success!", 200);
    }
    public function test1(Request $request)
    {
        //$data = $request->all();
        //$data['banana'];
        //return response('OK!', 200);
        //return response('Banana non trovata', 404);
        return 'test1';
    }
    
    public function test2(Request $request)
    {
        //Dipendente::where('id_dipendente', '=', 1)->orderBy('nome', 'ask')->get()->toArray();
        //$dipendenti = Dipendente::where('id_dipendente', '=', 1)->orderBy('nome', 'ask')->get();
        /*
        foreach($dipendenti as $dipendente){
            $dipendente->nome = $dipendente->nome.'banana!';
            $dipendente->save();
        }
        
        */
        return 'test2';
    }
}
