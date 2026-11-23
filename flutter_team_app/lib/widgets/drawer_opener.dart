import 'package:flutter/material.dart';

class DrawerOpener extends InheritedWidget {
  final VoidCallback openDrawer;
  final void Function(int) navigateTo;

  const DrawerOpener({
    super.key,
    required this.openDrawer,
    required this.navigateTo,
    required super.child,
  });

  static DrawerOpener? of(BuildContext context) {
    return context.dependOnInheritedWidgetOfExactType<DrawerOpener>();
  }

  @override
  bool updateShouldNotify(covariant DrawerOpener oldWidget) => false;
}
