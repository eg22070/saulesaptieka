<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    protected function ensureBrivibas()
    {
        if (!Auth::check() || strtolower(Auth::user()->role ?? '') !== 'brivibas') {
            abort(403);
        }
    }

    public function index()
    {
        $this->ensureBrivibas();
        $users = User::orderBy('id')->get();

        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $this->ensureBrivibas();
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|string|max:50',
        ]);

        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('users.index')->with('success', 'Lietotājs izveidots.');
    }

    public function update(Request $request, User $user)
    {
        $this->ensureBrivibas();
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role'  => 'required|string|max:50',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Lietotājs atjaunināts.');
    }
    public function destroy(User $user)
    {
        $this->ensureBrivibas();

        // Optional: prevent deleting yourself
        if (Auth::id() === $user->id) {
            return redirect()->route('users.index')
                ->with('success', 'Nevar dzēst pašreiz pieslēgušos lietotāju.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Lietotājs izdzēsts.');
    }
}
