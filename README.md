##  HTML-PDF-EXCEL

Thanks to
[TCPDF](https://github.com/tecnickcom/TCPDF) and [PHPSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet). This library is a helper to generate html, pdf, and excel from excel-based template and array data.

![Example](testing/example-design.png)

### Basic Syntax
| Code | Description |
| --- | --- |
|    v | text vertical    |
|    t | text with no border  |
|    r | align content to right   |
|    l | align content to left    |
|    c | align content to center  |
|    b | **bold**    |
|    h | header or column head  |
|    + | border all sides  |
|    [ | border left side |
|    ] | border right side    |
|    - | border top side  |
|    _ | border bottom side   |
|    = | border doubled   |
|    . | number separator formatting, Example result: from 1000000 becomes 1.000.000   |
|    w{number}% | width {number} %. Example: w25% will set the column width to 25 % of table width   |
|    u | underline   |
|    i | *italic*  |
|    g | ![#616469](https://via.placeholder.com/15/616469/000000?text=+) `gray background` |
|    y | ![#e8ff17](https://via.placeholder.com/15/e8ff17/000000?text=+) `yellow background`  |


### Inside Looping Syntax

| Code | Description |
| --- | --- |
|    $dataIndex | key data index  |
|    $detail.dataIndex | data index di detail    |
|    ? | space or empty cell  |
|    ! | grouping column and take the first cell value    |
|    _number | autonumberer starts from 1    |
|    ${looped_array_key}.{single_array_key} | Set value given from key  |


### Additional Features

You can also use any mathematic formula to get dynamic value as the image above such as summary or etc.