<?php

namespace Modules\Symbol\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SymbolResource extends ResourceCollection
{
    public function toArray(Request $request)
    {
        return $this->resource;
    }
}
