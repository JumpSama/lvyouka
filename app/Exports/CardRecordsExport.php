<?php
/**
 * Author: JumpSama
 * Date: 2019/4/29
 * Time: 14:38
 */

namespace App\Exports;


use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CardRecordsExport implements FromCollection, WithHeadings, WithEvents, WithMapping, WithColumnFormatting
{
    // 查询条件
    private $data;

    /**
     * 样式
     * @return array
     */
    public function registerEvents(): array
    {
        $styleArray = [
            'font' => [
                'bold' => true
            ],
            'alignment' => [
                'horizontal' => 'center'
            ]
        ];

        if ($this->data['pay_type'] == 2) {
            return [
                AfterSheet::class => function(AfterSheet $event) use ($styleArray) {
                    $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(15);
                    $event->sheet->getDelegate()->getColumnDimension('B')->setWidth(20);
                    $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(20);
                    $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(20);
                    $event->sheet->getDelegate()->getColumnDimension('E')->setWidth(20);
                    $event->sheet->getDelegate()->getStyle('A1:E1')->applyFromArray($styleArray);
                }
            ];
        }

        return [
            AfterSheet::class => function(AfterSheet $event) use ($styleArray) {
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('B')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('E')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('F')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('G')->setWidth(20);
                $event->sheet->getDelegate()->getStyle('A1:G1')->applyFromArray($styleArray);
            }
        ];
    }

    /**
     * 列格式
     * @return array
     */
    public function columnFormats(): array
    {
        if ($this->data['pay_type'] == 2) {
            return [ 'D' => NumberFormat::FORMAT_TEXT ];
        }

        return [ 'F' => NumberFormat::FORMAT_TEXT ];
    }

    /**
     * 表头
     * @return array
     */
    public function headings(): array
    {
        if ($this->data['pay_type'] == 2) {
            return ['类型', '会员姓名', '会员手机', '会员身份证', '开卡/续费时间'];
        }

        return ['操作员', '类型', '卡号', '会员姓名', '会员手机', '会员身份证', '开卡/续费时间'];
    }

    /**
     * 格式化
     * @param mixed $list
     * @return array
     */
    public function map($list): array
    {
        if ($this->data['pay_type'] == 2) {
            return [
                $list->show_type,
                $list->name,
                $list->phone,
                ' ' . $list->identity,
                $list->created_at
            ];
        }

        return [
            $list->show_name,
            $list->show_type,
            $list->number,
            $list->name,
            $list->phone,
            ' ' . $list->identity,
            $list->created_at
        ];
    }

    /**
     * 查询参数
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 查询数据
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $data = $this->data;

        $sql = DB::table('card_records as a')
            ->select(['a.created_at', 'a.user_id', 'a.member_id', 'a.type', 'a.pay_type', 'b.name as user_name', 'c.name', 'c.phone', 'c.identity', 'd.number'])
            ->leftJoin('users as b', 'a.user_id', '=', 'b.id')
            ->leftJoin('members as c', 'a.member_id', '=', 'c.id')
            ->leftJoin('cards as d', 'c.card_id', '=', 'd.id');

        if (isset($data['type'])) $sql = $sql->where('a.type', $data['type']);
        if (isset($data['pay_type'])) $sql = $sql->where('a.pay_type', $data['pay_type']);
        if (isset($data['user_keyword'])) {
            $userKeyword = $data['user_keyword'];
            $sql = $sql->where(function($q) use ($userKeyword) {
                $q->where('a.user_id', $userKeyword)
                    ->orWhere('b.name', 'like', '%'.$userKeyword.'%');
            });
        }
        if (isset($data['member_keyword'])) {
            $memberKeyword = $data['member_keyword'];
            $sql = $sql->where(function($q) use ($memberKeyword) {
                $q->where('c.name', 'like', '%'.$memberKeyword.'%')
                    ->orWhere('c.phone', 'like', '%'.$memberKeyword.'%')
                    ->orWhere('c.identity', 'like', '%'.$memberKeyword.'%');
            });
        }
        if (isset($data['start_time'])) $sql = $sql->where('a.created_at', '>=', $data['start_time'] . ' 00:00:00');
        if (isset($data['end_time'])) $sql = $sql->where('a.created_at', '<=', $data['end_time']. ' 23:59:59');

        return $sql->get()->each(function ($item) {
            $item->show_name = $item->user_name . '(ID:' . $item->user_id . ')';
            $item->show_type = $item->type == 1 ? '开卡' : '续费';
            return $item;
        });
    }
}