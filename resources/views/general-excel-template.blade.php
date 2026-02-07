<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        .center {
            text-align: center;
        }

        table .left {
            text-align: left;
        }

        table .right {
            text-align: right;
        }

        table .bold {
            font-weight: 600;
        }

        .bg-black {
            background-color: #000;
        }

        .f-white {
            color: #fff;
        }

        @foreach ($styles as $style)
        {{ $style['selector'] }}
        {
        {{ $style['style'] }}

        }
        @endforeach
    </style>
</head>
<body>
<?php
use SamuelTerra22\ReportGenerator\Support\AggregationHelper;
use SamuelTerra22\ReportGenerator\Support\ColumnFormatter;

$ctr = 1;
$no = 1;
$grandTotalSkip = 1;
$isOnSameGroup = true;
$currentGroupByData = [];

$aggState = AggregationHelper::init($showTotalColumns);

if ($showTotalColumns != []) {
    foreach ($columns as $colName => $colData) {
        if (!array_key_exists($colName, $showTotalColumns)) {
            $grandTotalSkip++;
        } else {
            break;
        }
    }
}

$grandTotalSkip = !$showNumColumn ? $grandTotalSkip - 1 : $grandTotalSkip;
?>
<table>
    <tr>
        <td colspan="{{ count($columns) + 1 }}" class="center"><h1>{{ $headers['title'] }}</h1></td>
    @if ($showMeta)
        @foreach($headers['meta'] as $name => $value)
            <tr>
                <td><b>{{ $name }}</b></td>
                <td colspan="{{ count($columns) }}">{{ ucwords($value) }}</td>
            </tr>
            @endforeach
            @endif
            </tr>
</table>
<table>
    @if ($showHeader)
        <thead>
        <tr>
            @if ($showNumColumn)
                <th class="left">No</th>
            @endif
            @foreach ($columns as $colName => $colData)
                @if (array_key_exists($colName, $editColumns))
                    <th class="{{ isset($editColumns[$colName]['class']) ? $editColumns[$colName]['class'] : 'left' }}">{{ $colName }}</th>
                @else
                    <th class="left">{{ $colName }}</th>
                @endif
            @endforeach
        </tr>
        </thead>
    @endif
    <?php
    $__env = isset($__env) ? $__env : null;
    $rowIndex = 0;
    ?>
    @foreach($query->take($limit ?: null)->cursor() as $result)
        <?php
        if ($groupByArr != []) {
            $isOnSameGroup = true;
            foreach ($groupByArr as $groupBy) {
                if (is_object($columns[$groupBy]) && $columns[$groupBy] instanceof Closure) {
                    $thisGroupByData[$groupBy] = $columns[$groupBy]($result);
                } else {
                    $thisGroupByData[$groupBy] = $result->{$columns[$groupBy]};
                }

                if (isset($currentGroupByData[$groupBy])) {
                    if ($thisGroupByData[$groupBy] != $currentGroupByData[$groupBy]) {
                        $isOnSameGroup = false;
                    }
                }

                $currentGroupByData[$groupBy] = $thisGroupByData[$groupBy];
            }

            if ($isOnSameGroup === false) {
                echo '<tr class="f-white">';
                if ($showNumColumn || $grandTotalSkip > 1) {
                    echo '<td class="bg-black" colspan="' . $grandTotalSkip . '"><b>Grand Total</b></td>';
                }
                $dataFound = false;
                foreach ($columns as $colName => $colData) {
                    if (array_key_exists($colName, $showTotalColumns)) {
                        echo '<td class="right bg-black"><b>' . AggregationHelper::formatResult($aggState, $colName) . '</b></td>';
                        $dataFound = true;
                    } else {
                        if ($dataFound) {
                            echo '<td class="bg-black"></td>';
                        }
                    }
                }
                echo '</tr>';

                // Reset No, Reset Grand Total
                $no = 1;
                AggregationHelper::reset($aggState);
                $isOnSameGroup = true;
            }
        }

        // Fire onRow callbacks
        if (isset($onRowCallbacks) && is_array($onRowCallbacks)) {
            foreach ($onRowCallbacks as $cb) {
                $cb($result, $rowIndex);
            }
        }
        ?>
        <tr align="center">
            @if ($showNumColumn)
                <td class="left">{{ $no }}</td>
            @endif
            @foreach ($columns as $colName => $colData)
                <?php
                $class = 'left';
                $condStyle = '';
                // Check Edit Column to manipulate class & Data
                if (is_object($colData) && $colData instanceof Closure) {
                    $generatedColData = $colData($result);
                } else {
                    $generatedColData = $result->{$colData};
                }
                $displayedColValue = $generatedColData;
                if (array_key_exists($colName, $editColumns)) {
                    if (isset($editColumns[$colName]['class'])) {
                        $class = $editColumns[$colName]['class'];
                    }

                    if (isset($editColumns[$colName]['displayAs'])) {
                        $displayAs = $editColumns[$colName]['displayAs'];
                        if (is_object($displayAs) && $displayAs instanceof Closure) {
                            $displayedColValue = $displayAs($result);
                        } elseif (!(is_object($displayAs) && $displayAs instanceof Closure)) {
                            $displayedColValue = $displayAs;
                        }
                    }
                } elseif (isset($columnFormats[$colName])) {
                    $fmt = $columnFormats[$colName];
                    $displayedColValue = ColumnFormatter::format($generatedColData, $fmt['type'], $fmt['options']);
                }

                if (array_key_exists($colName, $showTotalColumns)) {
                    AggregationHelper::update($aggState, $colName, $generatedColData);
                }

                // Conditional formatting
                if (isset($conditionalFormats[$colName])) {
                    foreach ($conditionalFormats[$colName] as $rule) {
                        if (($rule['condition'])($displayedColValue, $result)) {
                            if (isset($rule['styles']['class'])) {
                                $class .= ' ' . $rule['styles']['class'];
                            }
                            $inlineStyles = [];
                            foreach ($rule['styles'] as $prop => $val) {
                                if ($prop !== 'class') {
                                    $inlineStyles[] = $prop . ':' . $val;
                                }
                            }
                            if ($inlineStyles) {
                                $condStyle .= implode(';', $inlineStyles) . ';';
                            }
                        }
                    }
                }
                ?>
                <td class="{{ $class }}" @if($condStyle) style="{{ $condStyle }}" @endif>{{ $displayedColValue }}</td>
            @endforeach
        </tr>
        <?php $ctr++; $no++; $rowIndex++; ?>
    @endforeach
    @if ($showTotalColumns != [] && $ctr > 1)
        <tr class="f-white">
            @if ($showNumColumn || $grandTotalSkip > 1)
                <td colspan="{{ $grandTotalSkip }}" class="bg-black"><b>Grand Total</b></td> {{-- For Number --}}
            @endif
            <?php $dataFound = false; ?>
            @foreach ($columns as $colName => $colData)
                @if (array_key_exists($colName, $showTotalColumns))
                    <?php $dataFound = true; ?>
                    <td class="bg-black right"><b>{{ \SamuelTerra22\ReportGenerator\Support\AggregationHelper::formatResult($aggState, $colName) }}</b></td>
                @else
                    @if ($dataFound)
                        <td class="bg-black"></td>
                    @endif
                @endif
            @endforeach
        </tr>
    @endif
</table>
</body>
</html>
