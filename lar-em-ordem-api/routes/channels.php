<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('properties.{property}', fn(User $user, Property $property) => $user->can('view', $property));
