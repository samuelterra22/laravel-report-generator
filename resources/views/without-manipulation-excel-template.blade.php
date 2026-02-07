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
    </style>
</head>
<body>
<?php
use SamuelTerra22\ReportGenerator\Support\AggregationHelper;

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
                <th class="left">{{ $colName }}</th>
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
                        echo '<td class="left bg-black"><b>' . AggregationHelper::formatResult($aggState, $colName) . '</b></td>';
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
            <?php
            $data = $result->toArray();
            if (count($data) > count($columns)) array_pop($data);
            ?>
            @foreach ($data as $rowData)
                <td class="left">{{ $rowData }}</td>
            @endforeach
        </tr>
        <?php
        foreach ($showTotalColumns as $colName => $type) {
            AggregationHelper::update($aggState, $colName, $result->{$columns[$colName]});
        }
        $ctr++; $no++; $rowIndex++;
        ?>
    @endforeach
    @if ($showTotalColumns != [] && $ctr > 1)
        <tr class="f-white">
            <td colspan="{{ $grandTotalSkip }}" class="bg-black"><b>Grand Total</b></td> {{-- For Number --}}
            <?php $dataFound = false; ?>
            @foreach ($columns as $colName => $colData)
                @if (array_key_exists($colName, $showTotalColumns))
                    <?php $dataFound = true; ?>
                    <td class="bg-black left"><b>{{ \SamuelTerra22\ReportGenerator\Support\AggregationHelper::formatResult($aggState, $colName) }}</b></td>
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
