# LiveWire Datatables Bug Report

## High Priority Bugs

### 1. **SQL Injection Vulnerability in pinnedRecords** 
**File:** `src/Livewire/LivewireDatatable.php:1629`
```php
$this->query->orderBy(DB::raw('FIELD(id,' . implode(',', $this->pinnedRecords) . ')'), 'DESC');
```
**Issue:** The `$this->pinnedRecords` array is directly concatenated into a raw SQL query without validation or sanitization. If user input can control this array, it could lead to SQL injection.
**Severity:** Critical
**Fix:** Use parameter binding or validate/sanitize the pinnedRecords values.

### 2. **SQL Injection in Export Query**
**File:** `src/Livewire/LivewireDatatable.php:1808`
```php
return $query->havingRaw('checkbox_attribute IN (' . implode(',', $this->selected) . ')');
```
**Issue:** Similar SQL injection vulnerability where `$this->selected` array values are directly injected into SQL.
**Severity:** Critical
**Fix:** Use proper parameter binding with whereIn() instead of havingRaw().

### 3. **Uninitialized Properties in Column Class**
**File:** `src/Column.php:17-43`
```php
public array $tooltip;     // Uninitialized
public string $name;       // Uninitialized
public array $joins;       // Uninitialized
public string $filterView; // Uninitialized
public string $width;      // Uninitialized
public string $minWidth;   // Uninitialized
public string $maxWidth;   // Uninitialized
public string $aggregate;  // Uninitialized
public string $group;      // Uninitialized
```
**Issue:** These properties are declared but not initialized, causing PHP notices/errors when accessed before being set.
**Severity:** High
**Fix:** Initialize all properties with default values.

### 4. **Potential JSON Encoding Error in Callback Method**
**File:** `src/Column.php:129`
```php
$column->name = 'callback_' . ($callbackName ?? (string)crc32(json_encode(func_get_args())));
```
**Issue:** `json_encode()` can fail and return `false`, which would cause `crc32()` to generate an unexpected hash. No error handling for JSON encoding failure.
**Severity:** Medium
**Fix:** Add error handling for `json_encode()` failure.

### 5. **Missing Null Checks in Raw Column Creation**
**File:** `src/Column.php:99-104`
```php
$column->name = Str::after($raw, ' AS ');
$column->select = DB::raw(Str::before($raw, ' AS '));
$column->label = (string)Str::of($raw)->afterLast(' AS ')->replace('`', '');
$column->sort = (string)Str::of($raw)->beforeLast(' AS ');
```
**Issue:** No validation that `$raw` parameter contains ' AS '. If it doesn't, these operations may result in unexpected behavior.
**Severity:** Medium
**Fix:** Add validation to ensure `$raw` contains ' AS ' clause.

## Medium Priority Bugs

### 6. **Uninitialized Public Properties in LivewireDatatable**
**File:** `src/Livewire/LivewireDatatable.php:37-82`
```php
public $columns;          // Uninitialized
public $search;           // Uninitialized  
public $complex;          // Uninitialized
public $complexQuery;     // Uninitialized
public $title;            // Uninitialized
public $name;             // Uninitialized
public $userFilter;       // Uninitialized
public $actions;          // Uninitialized
public $massActionOption; // Uninitialized
```
**Issue:** These public properties are accessed throughout the code but not properly initialized, potentially causing undefined property warnings.
**Severity:** Medium
**Fix:** Initialize with appropriate default values.

### 7. **Array Access Without Existence Check**
**File:** `src/Livewire/LivewireDatatable.php:1681-1684`
```php
$callbackValue = array_key_exists($name, $callbacks) ? $callbacks[$name] : null;
$exportCallBackValue = array_key_exists($name, $exportCallbacks) ? $exportCallbacks[$name] : null;
$isEditable = array_key_exists($name, $editables);
if ($searchHighlightEnabled && array_key_exists($name, $searchableColumns)) {
```
**Issue:** While this code does check `array_key_exists`, it's inconsistent - the third line doesn't provide a default value.
**Severity:** Low
**Fix:** Make array access checking consistent.

### 8. **Potential Division by Zero in Pagination**
**File:** `src/Column.php:87-90`
```php
$column->callbackFunction = static function () use ($datatable) {
    return $datatable->getPage() * $datatable->perPage - $datatable->perPage + $datatable->row++;
};
```
**Issue:** If `$datatable->perPage` is 0, this calculation will behave unexpectedly.
**Severity:** Low
**Fix:** Add validation to ensure `perPage` is not zero.

### 9. **Potential String Manipulation Edge Cases**
**File:** `src/Column.php:74-80`
```php
if (Str::contains(Str::lower($name), ' as ')) {
    $column->name = array_reverse(preg_split('/ as /i', $name))[0];
    $column->label = array_reverse(preg_split('/ as /i', $name))[1];
    $column->base = preg_split('/ as /i', $name)[0];
}
```
**Issue:** No validation that the split operation produces the expected number of array elements. Accessing `[1]` could cause an array index error.
**Severity:** Low
**Fix:** Validate array size before accessing elements.

## Recommendations

1. **Input Validation**: Add comprehensive input validation for all user-controllable data, especially arrays that are used in SQL operations.

2. **Property Initialization**: Initialize all class properties with sensible default values to prevent undefined property errors.

3. **Error Handling**: Add proper error handling for operations that can fail (JSON encoding, string operations, etc.).

4. **SQL Security**: Replace all raw SQL concatenation with proper parameter binding.

5. **Type Safety**: Consider adding stricter type hints and validation to prevent type-related errors.

6. **Testing**: Implement comprehensive unit tests to catch these issues before they reach production.

## Priority Order for Fixes

1. Fix SQL injection vulnerabilities (Critical)
2. Initialize uninitialized properties (High)
3. Add proper error handling for JSON operations (Medium)
4. Improve input validation and edge case handling (Low)