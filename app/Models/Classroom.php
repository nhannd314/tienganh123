<?php

namespace App\Models;

use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasFactory;

    /**
     * Inline style of the colour badge of the classroom: a hue that stays the same for the classroom.
     */
    public function badgeStyle(): string
    {
        $hue = ($this->id * 47) % 360;

        return "background-color: hsl({$hue} 80% 50% / 0.16); color: hsl({$hue} 70% 30%); border-color: hsl({$hue} 80% 50% / 0.45);";
    }

    /**
     * @return HasMany<ClassSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }
}
