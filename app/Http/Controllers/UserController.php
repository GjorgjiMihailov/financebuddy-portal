<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('companies')
            ->latest()
            ->paginate(20);

        $users->through(fn (User $user) => [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'created_at' => $user->created_at,
            'role'       => $user->getRoleNames()->first(),
            'companies'  => $user->companies->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
        ]);

        return Inertia::render('users/Index', [
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('users/Create', [
            'companies' => Company::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
        ]);

        $user->assignRole($request->role);

        if ($request->role === 'company_admin' && ! empty($request->company_ids)) {
            $user->companies()->sync($request->company_ids);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Корисникот е успешно создаден.']);

        return to_route('users.index');
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('users/Edit', [
            'user' => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->getRoleNames()->first(),
                'company_ids' => $user->companies->pluck('id')->values(),
            ],
            'companies' => Company::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = [
            'name'  => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);
        $user->syncRoles([$request->role]);

        if ($request->role === 'company_admin') {
            $user->companies()->sync($request->company_ids ?? []);
        } else {
            $user->companies()->detach();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Промените се зачувани.']);

        return to_route('users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->companies()->detach();
        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Корисникот е избришан.']);

        return to_route('users.index');
    }
}
