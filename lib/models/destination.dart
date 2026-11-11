class Destination {
  const Destination({
    required this.id,
    required this.name,
    required this.description,
    required this.location,
    required this.category,
    this.slug = '',
    this.imageUrl,
    this.story,
    this.address,
    this.latitude,
    this.longitude,
  });

  final int id;
  final String name;
  final String description;
  final String location;
  final String category;
  final String slug;
  final String? imageUrl;
  final String? story;
  final String? address;
  final double? latitude;
  final double? longitude;

  factory Destination.fromJson(Map<String, dynamic> json) => Destination(
        id: _asInt(json['id']),
        name: _asString(json['nom'] ?? json['name']),
        description: _asString(json['description']),
        location: _asString(json['localite'] ?? json['location']),
        category: _asString(json['categorie'] ?? json['category']),
        slug: _asString(json['slug']),
        imageUrl: _asNullableString(json['image_principale'] ?? json['image_url']),
        story: _asNullableString(json['histoire']),
        address: _asNullableString(json['adresse']),
        latitude: _asDouble(json['latitude']),
        longitude: _asDouble(json['longitude']),
      );
}

int _asInt(Object? value) => value is num ? value.toInt() : int.tryParse('$value') ?? 0;
double? _asDouble(Object? value) => value is num ? value.toDouble() : double.tryParse('$value');
String _asString(Object? value) => value?.toString() ?? '';
String? _asNullableString(Object? value) => value?.toString();
