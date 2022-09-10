<?php

namespace App\Contact\ManageContactFeed\Web\Controllers;

use App\Contact\ManageContactFeed\Web\ViewHelpers\ModuleFeedViewHelper;
use App\Helpers\PaginatorHelper;
use App\Http\Controllers\Controller;
use App\Models\ContactFeedItem;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactFeedController extends Controller
{
    public function show(Request $request, int $vaultId, int $contactId)
    {
        $items = ContactFeedItem::where('contact_id', $contactId)
            ->with('author')
            ->with('contact')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $vault = Vault::find($vaultId);

        return response()->json([
            'data' => ModuleFeedViewHelper::data($items, Auth::user(), $vault),
            'paginator' => PaginatorHelper::getData($items),
        ], 200);
    }
}