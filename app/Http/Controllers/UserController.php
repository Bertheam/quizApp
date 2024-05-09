<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\UserPassword;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
 
    public function index()
    {
        if (auth()->guest()) {
            return redirect('/login');
        }
        $users = User::select(
            DB::raw('users.id as id'),
            DB::raw('users.nom as nom'),
            DB::raw('users.prenom as prenom'),
            DB::raw('users.email as email'),
            DB::raw('profils.libelle as profil '),
            DB::raw('departements.libelle as departement '),
            DB::raw('users.last_login_at as last_login_at '),
            DB::raw('users.telephone as telephone '),
            DB::raw('users.departements_id as departements_id '),
            DB::raw('users.adresse as adresse '),
            DB::raw('users.active as active '),
            DB::raw('users.fonction as fonction '),
        )
            ->leftJoin('profils', 'users.profils_id', 'profils.id')
            ->leftJoin('departements', 'users.departements_id', 'departements.id');


        if (auth()->user()->departements_id) {
            $users =  $users->where('users.departements_id', auth()->user()->departements_id);
        }

        $users =  $users->get()
            ->makeHidden(['password']);



        return view('pages.user.index', compact('users'));
    }


    public function store(Request $request)
    {

        request()->validate([
            'nom' => ['required'],
            'prenom' => ['required'],
            'email' => ['required', 'email', 'unique:users,email'],
            'profil' => ['required'],
            'adresse' => ['required'],
            'fonction' => ['required'],
            'photo' => 'image|mimes:jpeg,jpg,png|max:5048',
        ]);


        $active = 0;
        if (request('active')) {
            $active = 1;
        }

        $photo = $request->file('photo');

        $user = User::create([
            'prenom' => request('prenom'),
            'nom' => request('nom'),
            'genre' => request('genre'),
            'email' => request('email'),
            'profils_id' => request('profil'),
            'departements_id' => request('departement'),
            'telephone' => request('telephone'),
            'adresse' => request('adresse'),
            'fonction' => request('fonction'),
            'photo' => json_encode($photo),
            'active' => $active,
            'password' => bcrypt("azerty"),
        ]);
        if ($photo) {
            $filePath           = public_path() . '/uploads/files/photos_profil';
            $filename           = $user->id . '_' . uniqid() . '.' .  $photo->extension();
            $user->photo        =   '/uploads/files/photos_profil/' . $filename;
            $photo->move($filePath, $filename);
            $user->save();
        }

        if ($user) {
            // $this->_sendNewMail(
            //     'CHANGEMENT DE MOT DE PASSE',
            //     "<p>Cher/Chère <strong>" . $user->prenom . " " . $user->nom . "</strong>, <br><br>" .
            //         "Votre nouveau compte a été crée avec succès.<br>" .
            //         "Le mot de passe par défaut est: <strong>password</strong>.<br>" .
            //         "Afin de garantir la sécurité de votre compte, veuillez changer le mot de passe par défaut de votre compte utilisateur.<br>" .
            //         "Pour ce faire, veuillez suivre ces étapes :<br>" .
            //         "1. Connectez-vous à votre compte en utilisant vos identifiants actuels.<br>" .
            //         "2. Accédez à la section 'Mon Profil' en haut à droite de la plateforme.<br>" .
            //         "</p>",
            //         $user->email
            // );
            $this->logs("[ CREATION UTILISATEUR (NOM : " . $user->nom . ' ' . $user->prenom . ", ID : " . $user->id . ") ]");
            return redirect("user");
        }
        return back();
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (auth()->guest()) {
            return redirect('/login');
        }

        $user = User::findOrFail($id);


        return response($user,200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (auth()->guest()) {
            return redirect('/login');
        }

        $user  = User::findOrFail($id);

        return view('pages.user.edit', [
            'user' => $user
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        request()->validate([
            'nom' => ['required'],
            'prenom' => ['required'],
            'genre' => ['required'],
            'email' => ['required', 'min:4', 'email', Rule::unique('users', 'email')->ignore($id)],
            'fonction' => ['required'],
            'photo.*' => 'mimes:jpg,jpeg,png|max:2048'
        ]);

        $active = 0;
        if (request('active')) {
            $active = 1;
        }

        $user = User::findOrFail($id);

        $photo = json_decode($user->photo);

        $user->prenom = request('prenom');
        $user->nom = request('nom');
        $user->genre = request('genre');
        $user->email = request('email');
        $user->fonction = request('fonction');
        $user->telephone = request('telephone');
        $user->active = $active;

        $photo = $request->file('photo');
        if ($photo) {
            $filePath           = public_path() . '/uploads/files/photos_profil';
            $filename           = $user->id . '_' . uniqid() . '.' .  $photo->extension();
            $user->photo        =   '/uploads/files/photos_profil/' . $filename;
            $photo->move($filePath, $filename);
            $user->save();
        }

        if ($user->save()) {
            $this->logs("[ MODIFICATION UTILISATEUR (NOM : " . $user->nom . ' ' . $user->prenom . ", ID : " . $user->id . ") ]");
            // return redirect("user");
        }
        return redirect()->route('user.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->delete()) {
            $this->logs("[ SUPPRESSION UTILISATEUR NOM : " . $user->nom . ' ' . $user->prenom . ", ID : " . $id . ") ]");
            flash()->success('Succès !', 'Utilisateur supprimé avec succès.');
            return "done";
        } else
            flash()->warning('Erreur !', 'Impossible de supprimer ' . $user->prenom . " " . $user->nom);
        return back();
    }

    public function password(Request $request)
    {
        if (auth()->guest()) {
            return redirect('/login');
        }
        //method old password validation
        Validator::extend('old_password', function ($attribute, $value, $parameters, $validator) {
            return Hash::check($value, current($parameters));
        }, 'Ancien mot de passe incorrect.');

        $validator = Validator::make(request()->all(), [
            'ancien_mot_de_passe' => 'required|old_password:' . auth()->user()->password,
            'password' => ['required', 'confirmed', 'min:4'],
            'password_confirmation' => ['required'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $update = auth()->user()->update([
            'password' => bcrypt(request('password'))
        ]);

        if ($update) {
            flash()->success('Succès !', 'Mot de passe modifié avec succès.');
            return redirect()->intended();
        }

        return redirect()->back()->withErrors($validator);
    }

    // Login
    public function login(Request $request)
    {
        //verification
        if (auth()->check()) {
            return redirect()->route('dashboard.index');
        }
        return view('pages.user.login',);
    }

    //deconnexion
    public function deconnexion()
    {
        if (auth()->guest()) {
            return redirect('/login');
        }

        auth()->logout();

        return redirect('/login');
    }


    //Connexion
    public function connexion(Request $request)
    {
        //validation
        request()->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        // auth attempt laravel
        $resultat = auth()->attempt([
            'email' => request('email'),
            'password' => request('password'),
            'active' => 1
        ]);

        if ($resultat) {
            auth()->user()->update([
                'last_login_at' => Carbon::now()->toDateTimeString(),
                'last_login_ip' => request()->getClientIp(),
                'count_login' => \DB::raw('count_login + 1')
            ]);
            $this->logs("[ CONNEXION ]");

            if(auth()->user()->isDG()){
                return redirect()->route('dga.reception.DgaNonTraite');
            }
            if(auth()->user()->isApprovisionneur()){
                return redirect()->route('besoin.index');
            }
            return redirect()->intended('/menu');
        }

        return back()->withInput()->withErrors([
            'login' => 'E-mail ou mot de passe incorrect.',
        ]);
    }

    public function changePasswordStore(Request $request, $id)
    {

        $user = User::findOrFail($id);


        request()->validate([
            'ancien_mot_de_passe' => ['required'],
            'password' => ['required', 'confirmed', 'min:4'],
            'password_confirmation' => ['required'],
        ]);


        if (!Hash::check($request->ancien_mot_de_passe, $user->password)) {
            return back()->withInput()->withErrors([
                'ancien_mot_de_passe' => 'La valeur de ce champs est incorrect.',
            ]);
        }

        if (Hash::check($request->password, $user->password)) {
            return back()->withInput()->withErrors([
                "password" => "Le nouveau mot de passe doit être different de l'actuel mot de passe",
            ]);
        }

        $user->password = bcrypt($request->password);
        if ($user->save()) {
        }

        return redirect()->intended('/');
    }

    public function motDePasseSend(Request $request)
    {
        //verification
        if (auth()->check()) {
            return redirect()->route('dashboard.index');
        }
        $user_login = User::where('email', request('email_forget'))->where('active', 1)->first();


        if ($user_login) {
            $userPassword = UserPassword::create([
                'user_id' => $user_login->id,
                'statut' => 'cree',
                'expired_at' => Carbon::now()->addHour()->format('Y-m-d H:i:s'),
                'token' => Str::random(50)
            ]);

            $this->_sendNewMail(
                'RECUPERATION MOT DE PASSE',
                "<p>Bonjour <strong>" . $user_login->prenom . " " . $user_login->nom . "</strong>, <br>" .
                    "Une demande de réinitialisation du mot de passe a été reçue de votre compte.<br>" .
                    "Veuillez utiliser ce lien pour réinitialiser votre mot de passe<br><br>" .
                    route('mot_de_passe.lien', $userPassword->token) . "<br><br>" .
                    "<strong>Note :</strong> Ce lien est valable pendant une heure à partir du moment où il vous a été envoyé et ne peut être utilisé qu'une seule fois pour changer votre mot de passe.<br>" .
                    "</p>",
                $user_login->email
            );
            $message = "E-mail envoyé avec succès.";
            return view('pages.user.login', compact('message'));
        }

        return view('pages.user.login');
    }

    public function motDePasseChange(Request $request)
    {
        //verification
        if (auth()->check()) {
            return redirect()->route('dashboard.index');
        }
        request()->validate([
            'password' => ['required', 'confirmed', 'min:4'],
            'password_confirmation' => ['required'],
        ]);
        $user = User::findOrFail(request('user_id'));
        $user->password = bcrypt(request('password'));
        if ($user->save()) {
            $userPassword = UserPassword::findOrFail(request('id'));
            $userPassword->statut = "traite";
            $userPassword->save();

            flash()->success('Succès !', 'Mot de passe modifié avec succès.');
            return redirect()->route('login');
        }
        return back();
    }

}
