<?php

namespace App\Http\Controllers\Api;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Reponse;
use App\Models\Resultat;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\UserApiKey;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\UserApiCodePin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ApiController extends Controller
{

 
    public function authCheckIdentifiant(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');
        
        if (empty($email) || empty($password)) {
            return response()->json(
                ['error' => 'Veuillez renseigné les champs !'],
                400
            );
        }
        
        $user = User::where('email', $email)->first();
        if ($user) {
            $message = null;
            $codePinGenere = $this->genereteCodePin($user);
           
            return response()->json(
                [
                    "codePin" => $codePinGenere,
                    "message" => $message,
                ],
                200
            );
        } else {
            return response()->json(
                ['error' => 'User non trouvé'],
                404
            );
        }
    }
    public function genereteCodePin($user){
        $codePinGenere = $this->generateEmailCode();
        $userCodePin = new UserApiCodePin();
        $userCodePin->code_pin = $codePinGenere;
        $userCodePin->date_validite = now()->addMinutes(5);
        $userCodePin->user_id = $user->id;
        if ($userCodePin->save()) {
            $this->_sendNewSMS( '[CODE SBN-SANTE] : ' . $codePinGenere, [$user->telephone]);
        }
        // $userCodePin->save();
        return $codePinGenere;
    }

    public function authCheckSmsCode(Request $request)
    {
        $codePinEntree = $request->input('code_pin');
        $email = $request->input('email');

        $user = User::where('email', $email)
            ->first();
        if (!$user) {
            return response()->json(
                [
                    'error' => 'code incorrect'
                ],
                404
            );
        }

        $userCodePin = UserApiCodePin::where('user_id', $user->id)
            ->where('code_pin', $codePinEntree)
            ->first();

        if (!$userCodePin) {
            return response()->json(
                [
                    'error' => 'code introuvable'
                ],
                404
            );
        }

        if (now() > $userCodePin->date_validite) {
            return response()->json(
                [
                    'error' => 'code expiré'
                ],
                404
            );
        }

        //on supprime les autres code de cet user
        $deleteUserAutreApiKey = UserApiKey::where('user_id', $user->id)->delete();


        //on genere la nouvelle cle
        $userApiKey = new UserApiKey();
        $authKeyGenere = $this->generateAuthKey();
        $userApiKey->api_key = $authKeyGenere;
        $userApiKey->date_validite = null;
        $userApiKey->user_id = $user->id;
        $userApiKey->save();

        return response()->json(
            [
                'ApiKey' => $authKeyGenere,
                'date de validite ' => $userApiKey->date_validite
            ],
            200
        );
    }


    //fonction pour generer le code Sms
    public function generateEmailCode()
    {
        return rand(1000, 9999);
    }


    // fonction pour generer la cle
    public function generateAuthKey()
    {
        return  (string) Str::uuid();
    }

    private function getApiUser($request)
    {
        $api_key = $request->bearerToken();
        $userApiKey = UserApiKey::where('api_key', $api_key)->first();
        return User::find($userApiKey->user_id);
    }

    public function createUser(Request $request){

        // Validation des données
        $validator = Validator::make($request->all(), [
            'nom' => 'required',
            'prenom' => 'required',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:8',
            'role' => 'required|in:student,teacher',
            'filiere' => 'required_if:role,==,student',
            'classes' => 'required_if:role,==,teacher',
            'profession' => 'required_if:role,==,teacher',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Création de l'utilisateur
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'prenom' => $request->prenom,
            'filiere' => $request->filiere ?? null,
            'classes' => $request->classes ?? null,
            'profession' => $request->profession ?? null,
        ]);

        return response()->json(['user' => $user], 201);
    }

    public function createQuiz(Request $request)
    {

    }


    
}
