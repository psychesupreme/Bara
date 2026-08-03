<?php

namespace App\Services;

use App\Models\CommercialNode;
use App\Models\Customer;
use App\Models\User;
use App\Models\UserCommercialScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CommercialScopeResolver
{
    /**
     * Rebuild commercial_node_closure table for fast O(1) ancestor/descendant queries.
     */
    public function rebuildClosureTable(): void
    {
        DB::table('commercial_node_closure')->truncate();

        $nodes = CommercialNode::all();

        foreach ($nodes as $node) {
            // Self reference (depth 0)
            DB::table('commercial_node_closure')->insert([
                'ancestor_id' => $node->id,
                'descendant_id' => $node->id,
                'depth' => 0,
            ]);

            // Walk ancestor tree
            $depth = 1;
            $parent = $node->parent;
            while ($parent) {
                DB::table('commercial_node_closure')->insert([
                    'ancestor_id' => $parent->id,
                    'descendant_id' => $node->id,
                    'depth' => $depth,
                ]);

                $parent = $parent->parent;
                $depth++;
            }
        }
    }

    /**
     * Get all permitted commercial_node IDs for a given user.
     */
    public function getPermittedNodeIds(User $user): array
    {
        $scopes = UserCommercialScope::where('user_id', $user->id)
            ->where('effective_from', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now()->toDateString());
            })->get();

        if ($scopes->isEmpty()) {
            return [];
        }

        $nodeIds = [];

        foreach ($scopes as $scope) {
            $nodeIds[] = $scope->commercial_node_id;

            if ($scope->include_descendants) {
                $descendants = DB::table('commercial_node_closure')
                    ->where('ancestor_id', $scope->commercial_node_id)
                    ->pluck('descendant_id')
                    ->toArray();

                $nodeIds = array_merge($nodeIds, $descendants);
            }
        }

        return array_values(array_unique($nodeIds));
    }

    /**
     * Pre-query filtering for SFA queries (SS-004).
     */
    public function applyScopeFilter(Builder $query, User $user, string $commercialNodeColumn = 'commercial_node_id'): Builder
    {
        // System admin bypasses scope restrictions
        if ($user->role === 'admin') {
            return $query;
        }

        $permittedNodeIds = $this->getPermittedNodeIds($user);

        return $query->whereIn($commercialNodeColumn, $permittedNodeIds);
    }

    /**
     * Access Preview: Test what a selected user profile can see (SS-006).
     */
    public function previewUserAccess(User $targetUser): array
    {
        $nodeIds = $this->getPermittedNodeIds($targetUser);
        $nodes = CommercialNode::whereIn('id', $nodeIds)->get();
        $outlets = Customer::whereIn('commercial_node_id', $nodeIds)->get();

        return [
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'role' => $targetUser->role,
            ],
            'permitted_node_count' => count($nodeIds),
            'permitted_nodes' => $nodes,
            'permitted_outlet_count' => $outlets->count(),
            'permitted_outlets' => $outlets,
        ];
    }
}
