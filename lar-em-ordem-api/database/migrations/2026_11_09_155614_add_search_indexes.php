<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices para as consultas mais frequentes da API.
 * Cada índice segue a ordem do WHERE (igualdades primeiro) e termina na coluna do ORDER BY / intervalo.
 */
return new class extends Migration
{
    public function up(): void
    {
        // properties

        Schema::table('property_contracts', function (Blueprint $table) {
            $table->index(['property_id', 'resident_id', 'is_active'], 'contracts_property_resident_active_index');
            $table->index(['resident_id', 'is_active'], 'contracts_resident_active_index');
        });

        // vault

        Schema::table('vault_documents', function (Blueprint $table) {
              $table->index(['property_id', 'created_at'], 'vault_documents_property_created_index');
        });

        Schema::table('vault_notifications', function (Blueprint $table) {
            $table->index(['property_id', 'alert_date'], 'vault_notifications_property_alert_index');
            $table->index(['property_id', 'read_at'], 'vault_notifications_property_read_index');
        });

        // invoices and consumptions

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['resident_id', 'created_at'], 'invoices_resident_created_index');
        });

        Schema::table('consumptions', function (Blueprint $table) {
            $table->index(['property_id', 'period_start'], 'consumptions_property_period_index');
        });

        Schema::table('consumption_benchmarks', function (Blueprint $table) {
            $table->index(
                ['property_type_id', 'typology_id', 'region', 'reference_period', 'period_start'],
                'cb_dimensions_period_index'
            );
        });

        Schema::table('files', function (Blueprint $table) {
            $table->index(['user_id', 'category'], 'files_user_category_index');
        });

        // assistance

        Schema::table('assistance_requests', function (Blueprint $table) {
            $table->index(['property_id', 'status', 'created_at'], 'requests_property_status_created_index');
            $table->index(['status', 'created_at'], 'requests_status_created_index');
            $table->dropIndex(['status']);
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->index(['service_provider_id', 'status', 'created_at'], 'proposals_provider_status_created_index');
        });

        // parcerias 

        Schema::table('offers', function (Blueprint $table) {
            $table->index(['active', 'start_date'], 'offers_active_start_index');
            $table->index(['partner_id', 'start_date'], 'offers_partner_start_index');
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->index('company_name', 'partners_company_name_index');
        });

        // conduminuim

        Schema::table('condominium_notices', function (Blueprint $table) {
            $table->index(['condominium_id', 'published_at'], 'condo_notices_condo_published_index');
        });

        Schema::table('condominium_documents', function (Blueprint $table) {
            $table->index(['condominium_id', 'document_date'], 'condo_documents_condo_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('condominium_documents', fn (Blueprint $table) => $table->dropIndex('condo_documents_condo_date_index'));
        Schema::table('condominium_notices', fn (Blueprint $table) => $table->dropIndex('condo_notices_condo_published_index'));
        Schema::table('partners', fn (Blueprint $table) => $table->dropIndex('partners_company_name_index'));

        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex('offers_partner_start_index');
            $table->dropIndex('offers_active_start_index');
        });

        Schema::table('proposals', fn (Blueprint $table) => $table->dropIndex('proposals_provider_status_created_index'));

        Schema::table('assistance_requests', function (Blueprint $table) {
            $table->index('status');
            $table->dropIndex('requests_status_created_index');
            $table->dropIndex('requests_property_status_created_index');
        });

        Schema::table('files', fn (Blueprint $table) => $table->dropIndex('files_user_category_index'));
        Schema::table('consumption_benchmarks', fn (Blueprint $table) => $table->dropIndex('cb_dimensions_period_index'));
        Schema::table('consumptions', fn (Blueprint $table) => $table->dropIndex('consumptions_property_period_index'));
        Schema::table('invoices', fn (Blueprint $table) => $table->dropIndex('invoices_resident_created_index'));

        Schema::table('vault_notifications', function (Blueprint $table) {
            $table->dropIndex('vault_notifications_property_read_index');
            $table->dropIndex('vault_notifications_property_alert_index');
        });

        Schema::table('vault_documents', fn (Blueprint $table) => $table->dropIndex('vault_documents_property_created_index'));

        Schema::table('property_contracts', function (Blueprint $table) {
            $table->dropIndex('contracts_resident_active_index');
            $table->dropIndex('contracts_property_resident_active_index');
        });
    }
};