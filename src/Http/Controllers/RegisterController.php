<?php

namespace Bitfumes\Multiauth\Http\Controllers;

use Illuminate\Http\Request;
use Bitfumes\Multiauth\Model\Role;
use Illuminate\Routing\Controller;
use Bitfumes\Multiauth\Model\Admin;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Bitfumes\Multiauth\Http\Requests\AdminRequest;
use Bitfumes\Multiauth\Notifications\RegistrationNotification;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    public function redirectTo()
    {
        return $this->redirectTo = route('admin.show');
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('super:admin');
    }

    public function showRegistrationForm()
    {
        $roles = Role::all();

        return view('multiauth::admin.register', compact('roles'));
    }

    public function register(AdminRequest $request)
    {
        event(new Registered($user = $this->create($request->all())));

        return redirect($this->redirectPath());
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     *
     * @return Admin
     */
    protected function create(array $data)
    {
        $admin = new Admin;

        $fields = $this->tableFields();

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $admin->$field = $data[$field];
            }
        }

        $admin->save();

        $admin->roles()->sync(request('role_id'));
        $admin->notify(new RegistrationNotification(request('password')));
        // dd('asdf');

        return $admin;
    }

    protected function tableFields()
    {
        return collect(\Schema::getColumnListing('admins'));
    }

    public function edit(Admin $admin)
    {
        $roles = Role::all();

        return view('multiauth::admin.edit', compact('admin', 'roles'));
    }

    public function update(Admin $admin, AdminRequest $request)
    {
        $admin->update($request->except('role_id'));
        $admin->roles()->sync(request('role_id'));

        return redirect(route('admin.show'))->with('message', "{$admin->name} details are successfully updated");
    }

    public function destroy(Admin $admin)
    {
        $prefix = config('multiauth.prefix');
        $admin->delete();

        return redirect(route('admin.show'))->with('message', "You have deleted {$prefix} successfully");
    }
}
