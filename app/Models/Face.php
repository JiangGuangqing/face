<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Face extends Model {

    protected $fillable = ['user_id','face_token','img','glasses','emotion','expression','beauty'];

    protected $dates = [];

    public static $rules = [
        // Validation rules
    ];

    // Relationships

}
