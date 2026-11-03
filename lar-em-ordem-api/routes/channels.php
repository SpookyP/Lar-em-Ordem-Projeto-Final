<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Property\Property;
use App\Models\User\User;

Broadcast::channel('properties.{property}', fn(User $user, Property $property) => $user->can('view', $property));