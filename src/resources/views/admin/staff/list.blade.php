@extends('layouts.admin-app') {{-- 管理者用の共通レイアウトを継承 --}}

@section('title', 'スタッフ一覧') {{-- ページタイトルを「スタッフ一覧」に設定 --}}

@section('css')
    {{-- カスタムCSSファイルを読み込み --}}
    <link href="{{ asset('css/admin-staff-list.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container">
    <h2 class="page-title">スタッフ一覧</h2> {{-- ページタイトルを「スタッフ一覧」に設定 --}}

    {{-- Success/Error messages --}}
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="table-responsive">
        <table class="staff-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                {{-- $staffs は Admin\StaffController から渡されるユーザーコレクションを想定 --}}
                @forelse ($staffs as $staff)
                <tr>
                    <td data-label="名前">{{ $staff->name }}</td> {{-- FN041: 氏名を表示 --}}
                    <td data-label="メールアドレス">{{ $staff->email }}</td> {{-- FN041: メールアドレスを表示 --}}
                    <td data-label="詳細">
                        {{-- FN042: 各ユーザーの月次勤怠一覧へ遷移するリンク --}}
                        {{-- 現在の月をデフォルトで渡すため、Carbon::now()->format('Y-m') を使用 --}}
                        <a href="{{ route('admin.staff.attendance', ['id' => $staff->id, 'month' => \Carbon\Carbon::now()->format('Y-m')]) }}" class="action-button detail-button">詳細</a>
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="3" class="no-data">スタッフ情報がありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ページネーションリンク --}}
    <div class="pagination-links">
        {{ $staffs->links() }}
    </div>
</div>
@endsection
