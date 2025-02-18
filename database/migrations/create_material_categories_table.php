use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaterialCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('material_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->foreign('material_category_id')
                ->references('id')
                ->on('material_categories')
                ->onDelete('cascade'); // Esto borrará todas las solicitudes asociadas
        });
    }

    public function down()
    {
        Schema::dropIfExists('material_categories');
    }
}
