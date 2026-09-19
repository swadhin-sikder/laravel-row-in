<?php

namespace Illuminate\Database\Query {
    /**
     * IDE-only declarations for Laravel Row In query builder macros.
     *
     * @method $this whereRowIn(list<string> $columns, mixed $values, string $boolean = 'and')
     * @method $this whereNotRowIn(list<string> $columns, mixed $values, string $boolean = 'and')
     * @method $this orWhereRowIn(list<string> $columns, mixed $values)
     * @method $this orWhereNotRowIn(list<string> $columns, mixed $values)
     */
    class Builder {}
}

namespace Illuminate\Database\Eloquent {
    /**
     * IDE-only declarations for Laravel Row In query builder macros forwarded by Eloquent.
     *
     * @method $this whereRowIn(list<string> $columns, mixed $values, string $boolean = 'and')
     * @method $this whereNotRowIn(list<string> $columns, mixed $values, string $boolean = 'and')
     * @method $this orWhereRowIn(list<string> $columns, mixed $values)
     * @method $this orWhereNotRowIn(list<string> $columns, mixed $values)
     */
    class Builder {}
}
