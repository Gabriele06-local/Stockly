<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Stockly</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="min-h-screen bg-slate-900 flex items-center justify-center p-4">
<div class="w-full max-w-md bg-white rounded-2xl p-8 shadow-xl">
  <div class="flex items-center gap-3 mb-6"><div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-xl">S</div>
  <div><div class="font-bold text-lg">Stockly</div><div class="text-xs text-slate-500">Sign in to your store</div></div></div>
  @if($errors->any())<div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
  <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">@csrf
    <div><label class="text-sm font-medium">Email</label><input name="email" type="email" value="{{ old('email','admin@stockly.test') }}" required class="mt-1 w-full border rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none"></div>
    <div><label class="text-sm font-medium">Password</label><input name="password" type="password" value="password" required class="mt-1 w-full border rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none"></div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" class="rounded"> Remember me</label>
    <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl py-2.5 font-medium">Sign in</button>
  </form>
  <div class="mt-6 text-xs text-slate-500 bg-slate-50 rounded-xl p-3">Demo accounts:<br><b>admin@stockly.test / password</b> (admin)<br><b>staff@stockly.test / password</b> (staff)</div>
</div>
</body></html>
