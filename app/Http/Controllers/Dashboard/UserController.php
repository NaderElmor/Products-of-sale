<?php

namespace App\Http\Controllers\Dashboard;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:read_users'])->only('index');
        $this->middleware(['permission:create_users'])->only('create');
        $this->middleware(['permission:update_users'])->only('edit');
        $this->middleware(['permission:delete_users'])->only('destroy');
    }

    public function index(Request $request)
    {
        //search way #1
        /*
            if($request->search) {
                $users = User::whereRoleIs('admin')->where('name','like','%'.$request->search. '%')
                                                   ->orWhere('email','like','%'. $request->search .'%')->get();
            }else{                               
                $users = User::whereRoleIs('admin')->get();
            }
*/

            

        //search (Better) #2
         $users = User::whereRoleIs('admin')->when($request->search, function($query) use ($request){

            return $query->where('name','like','%'. $request->search .'%')
                         ->orWhere('email','like','%'. $request->search .'%');

            })->latest()->paginate(2); 

       
        return view('dashboard.users.index', compact('users'));
    }


    public function create()
    {
        return view('dashboard.users.create');
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:50',
            'email' => 'required|unique:users',
            'password' => 'required|confirmed'
        ]);

        $req_data = $request->except(['password','password_confirmation', 'permissions']);

        //encrypt user
        $req_data['password'] =  bcrypt($request->password);


        //create permissions to the user
        $user = User::create($req_data);

        $user->attachRole('admin');
        $user->syncPermissions($request->permissions);

        session()->flash('success', __('site.added_successfully'));

       return redirect()->route('dashboard.users.index');
    }


    public function show(User $user)
    {
        //
    }


    public function edit(User $user)
    {
        return view('dashboard.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|max:50',
            'email' => 'required',
        ]);

        $req_data = $request->except(['permissions']);



        $user->update($req_data);

        //create permissions to the user
        $user->syncPermissions($request->permissions);

        session()->flash('success', __('site.updated_successfully'));

       return redirect()->route('dashboard.users.index');
    }


    public function destroy(User $user)
    {
        $user->delete();
        session()->flash('success', __('site.deleted_successfully'));
        return redirect()->route('dashboard.users.index');

    }
}
