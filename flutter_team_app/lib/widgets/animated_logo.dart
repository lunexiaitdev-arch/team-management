import 'package:flutter/material.dart';
import '../theme/app_colors.dart';

class AnimatedLogo extends StatefulWidget {
  final double size;
  final bool showLiveIndicator;
  final bool enablePulse;

  const AnimatedLogo({
    super.key,
    this.size = 36.0,
    this.showLiveIndicator = false,
    this.enablePulse = true,
  });

  @override
  State<AnimatedLogo> createState() => _AnimatedLogoState();
}

class _AnimatedLogoState extends State<AnimatedLogo> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _glowAnimation;
  late Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2200),
    );

    if (widget.enablePulse) {
      _glowAnimation = Tween<double>(begin: 4.0, end: 14.0).animate(
        CurvedAnimation(parent: _controller, curve: Curves.easeInOut),
      );

      _scaleAnimation = Tween<double>(begin: 0.97, end: 1.03).animate(
        CurvedAnimation(parent: _controller, curve: Curves.easeInOut),
      );

      _controller.repeat(reverse: true);
    } else {
      _glowAnimation = const AlwaysStoppedAnimation(6.0);
      _scaleAnimation = const AlwaysStoppedAnimation(1.0);
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final logoSize = widget.size;
    final borderRadius = BorderRadius.circular(logoSize * 0.24);

    return RepaintBoundary(
      child: AnimatedBuilder(
        animation: _controller,
        builder: (context, child) {
          final glowRadius = widget.enablePulse ? _glowAnimation.value : 6.0;
          final scale = widget.enablePulse ? _scaleAnimation.value : 1.0;

          return Transform.scale(
            scale: scale,
            child: Stack(
              clipBehavior: Clip.none,
              alignment: Alignment.center,
              children: [
                // Outer Breathing Glow & Logo Container
                Container(
                  width: logoSize,
                  height: logoSize,
                  decoration: BoxDecoration(
                    borderRadius: borderRadius,
                    boxShadow: [
                      BoxShadow(
                        color: AppColors.primaryBlueLight.withValues(alpha: 0.45),
                        blurRadius: glowRadius,
                        spreadRadius: widget.enablePulse ? (glowRadius * 0.15) : 1.0,
                      ),
                      BoxShadow(
                        color: AppColors.cyanAccent.withValues(alpha: 0.25),
                        blurRadius: glowRadius * 1.5,
                        spreadRadius: 0.5,
                      ),
                    ],
                  ),
                  child: ClipRRect(
                    borderRadius: borderRadius,
                    child: Image.asset(
                      'assets/images/logo.webp',
                      width: logoSize,
                      height: logoSize,
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) => Image.asset(
                        'assets/images/logo.png',
                        width: logoSize,
                        height: logoSize,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) => Container(
                          decoration: BoxDecoration(
                            gradient: AppColors.brandGradient,
                            borderRadius: borderRadius,
                          ),
                          child: Icon(
                            Icons.bolt_rounded,
                            color: Colors.white,
                            size: logoSize * 0.65,
                          ),
                        ),
                      ),
                    ),
                  ),
                ),

                // Optional Pulsing Live Radar Indicator Dot
                if (widget.showLiveIndicator)
                  Positioned(
                    right: -2,
                    bottom: -2,
                    child: Container(
                      width: (logoSize * 0.26).clamp(8.0, 14.0),
                      height: (logoSize * 0.26).clamp(8.0, 14.0),
                      decoration: BoxDecoration(
                        color: AppColors.success,
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: Theme.of(context).scaffoldBackgroundColor,
                          width: 1.5,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: AppColors.success.withValues(alpha: 0.6),
                            blurRadius: 4,
                            spreadRadius: 1,
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}
